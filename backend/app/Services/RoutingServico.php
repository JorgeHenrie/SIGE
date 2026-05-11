<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;

class RoutingServico
{
    private const LIMIAR_ECONOMIA_KM = 0.05;
    private const LIMIAR_ECONOMIA_CUSTO = 0.10;

    private array $distanceCache = [];

    public function processar(array $visitas, array $origem, string $transporte, string $dataRoteiro): array
    {
        $this->distanceCache = [];

        $clusters = $this->indexarClusters($visitas);
        $sequencia = $this->ordenarClusters($clusters, $origem, $transporte, $dataRoteiro);
        $simulacao = $this->simularSequencia($sequencia['visitas'], $origem, $transporte, $dataRoteiro);
        $baselineVisitas = $visitas;
        usort($baselineVisitas, fn (array $a, array $b): int => ((int) ($a['original_index'] ?? 0)) <=> ((int) ($b['original_index'] ?? 0)));
        $baseline = $this->simularSequencia($baselineVisitas, $origem, $transporte, $dataRoteiro);
        $economia = $this->calcularEconomiaComparativa(
            $baseline['distancia_total_km'],
            $baseline['tempo_total_min'],
            $simulacao['distancia_total_km'],
            $simulacao['tempo_total_min'],
            $transporte
        );

        $visitasComDesvio = $this->marcarDesvios($simulacao['visitas'], $origem, $simulacao['distancia_total_km']);
        $agrupamentos = $this->montarAgrupamentos($visitasComDesvio);
        $custoEstimado = $this->estimarCusto($simulacao['distancia_total_km'], $transporte);
        $visitasDesvio = array_values(array_filter(
            $visitasComDesvio,
            fn (array $visita): bool => !empty($visita['desvio_relevante'])
        ));

        $logs = array_merge($sequencia['logs'], $simulacao['logs']);
        foreach ($visitasDesvio as $visita) {
            $logs[] = [
                'tipo' => 'desvio',
                'mensagem' => sprintf('A visita "%s" foi sinalizada como desvio relevante. %s', $visita['nome'], $visita['motivo_desvio']),
            ];
        }

        $logs[] = [
            'tipo' => 'economia',
            'mensagem' => $this->descreverEconomia($economia),
        ];

        return [
            'visitas' => $visitasComDesvio,
            'agrupamentos' => $agrupamentos,
            'distancia_total_km' => round($simulacao['distancia_total_km'], 2),
            'tempo_total_min' => $simulacao['tempo_total_min'],
            'custo_estimado' => $custoEstimado,
            'distancia_baseline_km' => round($baseline['distancia_total_km'], 2),
            'tempo_baseline_min' => $baseline['tempo_total_min'],
            'custo_baseline_estimado' => $economia['custo_baseline_estimado'],
            'economia_km' => $economia['economia_km'],
            'economia_percentual' => $economia['economia_percentual'],
            'economia_tempo_min' => $economia['economia_tempo_min'],
            'economia_tempo_percentual' => $economia['economia_tempo_percentual'],
            'economia_custo_estimado' => $economia['economia_custo_estimado'],
            'visitas_desvio' => $visitasDesvio,
            'logs_decisao' => $logs,
            'sugestao_melhor_roteiro' => $this->gerarResumo($origem, $agrupamentos, $economia, $visitasDesvio),
        ];
    }

    public function calcularEconomiaComparativa(
        float $distanciaBaselineKm,
        int $tempoBaselineMin,
        float $distanciaAtualKm,
        int $tempoAtualMin,
        string $transporte
    ): array {
        $economiaKmBruta = max(0, $distanciaBaselineKm - $distanciaAtualKm);
        $economiaKm = $economiaKmBruta >= self::LIMIAR_ECONOMIA_KM
            ? round($economiaKmBruta, 2)
            : 0.0;
        $economiaPercentual = $distanciaBaselineKm > 0
            ? round(($economiaKm / $distanciaBaselineKm) * 100, 2)
            : 0.0;
        $economiaTempoMin = max(0, $tempoBaselineMin - $tempoAtualMin);
        $economiaTempoPercentual = $tempoBaselineMin > 0
            ? round(($economiaTempoMin / $tempoBaselineMin) * 100, 2)
            : 0.0;
        $custoBaselineEstimado = $this->estimarCusto($distanciaBaselineKm, $transporte);
        $custoAtualEstimado = $this->estimarCusto($distanciaAtualKm, $transporte);
        $economiaCustoBruta = max(0, $custoBaselineEstimado - $custoAtualEstimado);
        $economiaCustoEstimado = $economiaCustoBruta >= self::LIMIAR_ECONOMIA_CUSTO
            ? round($economiaCustoBruta, 2)
            : 0.0;

        return [
            'custo_baseline_estimado' => $custoBaselineEstimado,
            'economia_km' => $economiaKm,
            'economia_percentual' => $economiaPercentual,
            'economia_tempo_min' => $economiaTempoMin,
            'economia_tempo_percentual' => $economiaTempoPercentual,
            'economia_custo_estimado' => $economiaCustoEstimado,
        ];
    }

    public function enriquecerSequenciaPersistida(array $visitas, array $origem, string $transporte, string $dataRoteiro): array
    {
        $sequencia = $visitas;
        usort($sequencia, fn (array $a, array $b): int => ((int) ($a['ordem_sugerida'] ?? 0)) <=> ((int) ($b['ordem_sugerida'] ?? 0)));

        return $this->simularSequencia($sequencia, $origem, $transporte, $dataRoteiro);
    }

    private function ordenarClusters(array $clusters, array $origem, string $transporte, string $dataRoteiro): array
    {
        $pendentes = $clusters;
        $logs = [];
        $sequencia = [];
        $pontoAtual = [
            'latitude' => (float) $origem['latitude'],
            'longitude' => (float) $origem['longitude'],
        ];
        $tempoAtual = $this->definirHoraInicial($dataRoteiro, array_merge(...array_values(array_map(fn (array $cluster): array => $cluster['visitas'], $clusters))));

        while ($pendentes !== []) {
            $selecionadoId = null;
            $melhorScore = null;
            $motivo = '';

            foreach ($pendentes as $clusterId => $cluster) {
                $avaliacao = $this->avaliarCluster($cluster, $pontoAtual, $tempoAtual);
                if ($melhorScore === null || $avaliacao['score'] < $melhorScore) {
                    $melhorScore = $avaliacao['score'];
                    $selecionadoId = $clusterId;
                    $motivo = $avaliacao['motivo'];
                }
            }

            if ($selecionadoId === null) {
                break;
            }

            $cluster = $pendentes[$selecionadoId];
            $logs[] = [
                'tipo' => 'roteamento',
                'mensagem' => sprintf('O cluster %d foi escolhido em seguida porque %s.', $cluster['id'], $motivo),
            ];

            $ordenacao = $this->ordenarVisitasCluster($cluster['visitas'], $pontoAtual, $tempoAtual, $transporte);
            $sequencia = array_merge($sequencia, $ordenacao['visitas']);
            $logs = array_merge($logs, $ordenacao['logs']);
            $pontoAtual = $ordenacao['ponto_atual'];
            $tempoAtual = $ordenacao['tempo_atual'];

            unset($pendentes[$selecionadoId]);
        }

        return ['visitas' => $sequencia, 'logs' => $logs];
    }

    private function ordenarVisitasCluster(array $visitas, array $pontoInicial, DateTimeImmutable $tempoInicial, string $transporte): array
    {
        $pendentes = array_values($visitas);
        $ordenadas = [];
        $logs = [];
        $pontoAtual = $pontoInicial;
        $tempoAtual = $tempoInicial;

        while ($pendentes !== []) {
            $selecionadoIndice = 0;
            $melhorScore = null;
            $motivo = '';

            foreach ($pendentes as $indice => $visita) {
                $avaliacao = $this->avaliarVisita($visita, $pontoAtual, $tempoAtual, $transporte);

                if ($melhorScore === null || $avaliacao['score'] < $melhorScore) {
                    $melhorScore = $avaliacao['score'];
                    $selecionadoIndice = $indice;
                    $motivo = $avaliacao['motivo'];
                }
            }

            $visita = $pendentes[$selecionadoIndice];
            $visita['justificativa_ordem'] = $motivo;
            $ordenadas[] = $visita;
            $logs[] = [
                'tipo' => 'roteamento',
                'mensagem' => sprintf('A visita "%s" entrou na próxima posição porque %s.', $visita['nome'], $motivo),
            ];

            unset($pendentes[$selecionadoIndice]);
            $pendentes = array_values($pendentes);

            $estimativa = $this->simularPasso($visita, $pontoAtual, $tempoAtual, $transporte);
            $pontoAtual = [
                'latitude' => (float) $visita['latitude'],
                'longitude' => (float) $visita['longitude'],
            ];
            $tempoAtual = $estimativa['fim'];
        }

        return [
            'visitas' => $ordenadas,
            'logs' => $logs,
            'ponto_atual' => $pontoAtual,
            'tempo_atual' => $tempoAtual,
        ];
    }

    private function simularSequencia(array $visitas, array $origem, string $transporte, string $dataRoteiro): array
    {
        $logs = [];
        $pontoAtual = [
            'latitude' => (float) $origem['latitude'],
            'longitude' => (float) $origem['longitude'],
        ];
        $tempoAtual = $this->definirHoraInicial($dataRoteiro, $visitas);
        $tempoTotal = 0;
        $distanciaTotal = 0.0;
        $enriquecidas = [];

        foreach (array_values($visitas) as $indice => $visita) {
            $passo = $this->simularPasso($visita, $pontoAtual, $tempoAtual, $transporte);

            $visita['ordem_sugerida'] = $indice + 1;
            $visita['distancia_incremental_km'] = round($passo['distancia_km'], 2);
            $visita['tempo_incremental_min'] = $passo['tempo_deslocamento_min'];
            $visita['hora_estimada_chegada'] = $passo['chegada']->format('Y-m-d H:i:s');
            $visita['hora_estimada_inicio'] = $passo['inicio']->format('Y-m-d H:i:s');
            $visita['hora_estimada_fim'] = $passo['fim']->format('Y-m-d H:i:s');
            $visita['espera_min'] = $passo['espera_min'];
            $visita['atraso_janela_min'] = $passo['atraso_janela_min'];

            if (!isset($visita['justificativa_ordem'])) {
                $visita['justificativa_ordem'] = sprintf(
                    'Mantida na ordem %d com deslocamento estimado de %.2f km.',
                    $indice + 1,
                    $passo['distancia_km']
                );
            }

            $logs[] = [
                'tipo' => 'sequencia',
                'mensagem' => sprintf(
                    'A visita "%s" ficou na posição %d com %.2f km de deslocamento, %d min de trajeto e %d min de espera.',
                    $visita['nome'],
                    $indice + 1,
                    $passo['distancia_km'],
                    $passo['tempo_deslocamento_min'],
                    $passo['espera_min']
                ),
            ];

            $distanciaTotal += $passo['distancia_km'];
            $tempoTotal += $passo['tempo_deslocamento_min'] + $passo['espera_min'] + $passo['duracao_visita_min'];
            $pontoAtual = [
                'latitude' => (float) $visita['latitude'],
                'longitude' => (float) $visita['longitude'],
            ];
            $tempoAtual = $passo['fim'];
            $enriquecidas[] = $visita;
        }

        return [
            'visitas' => $enriquecidas,
            'distancia_total_km' => $distanciaTotal,
            'tempo_total_min' => $tempoTotal,
            'logs' => $logs,
        ];
    }

    private function simularPasso(array $visita, array $pontoAtual, DateTimeImmutable $tempoAtual, string $transporte): array
    {
        $distancia = $this->distanciaEntrePontos($pontoAtual, $visita);
        $tempoDeslocamento = $this->estimarTempoDeslocamento($distancia, $transporte);
        $chegada = $tempoAtual->modify(sprintf('+%d minutes', $tempoDeslocamento));
        $janelaInicio = $this->parseDataHora($visita['horario_inicio'] ?? null);
        $janelaFim = $this->parseDataHora($visita['horario_fim'] ?? null);

        $inicio = $chegada;
        $espera = 0;
        if ($janelaInicio && $chegada < $janelaInicio) {
            $espera = $this->diferencaMinutos($chegada, $janelaInicio);
            $inicio = $janelaInicio;
        }

        $atraso = 0;
        if ($janelaFim && $inicio > $janelaFim) {
            $atraso = $this->diferencaMinutos($janelaFim, $inicio);
        }

        $duracao = $this->duracaoVisita((string) ($visita['prioridade'] ?? 'media'));
        $fim = $inicio->modify(sprintf('+%d minutes', $duracao));

        return [
            'distancia_km' => $distancia,
            'tempo_deslocamento_min' => $tempoDeslocamento,
            'chegada' => $chegada,
            'inicio' => $inicio,
            'fim' => $fim,
            'espera_min' => $espera,
            'atraso_janela_min' => $atraso,
            'duracao_visita_min' => $duracao,
        ];
    }

    private function marcarDesvios(array $visitas, array $origem, float $distanciaTotal): array
    {
        if (count($visitas) < 3) {
            return $visitas;
        }

        foreach ($visitas as $indice => $visita) {
            $anterior = $indice === 0 ? $origem : $visitas[$indice - 1];
            $proxima = $visitas[$indice + 1] ?? null;

            $distanciaAnterior = $this->distanciaEntrePontos($anterior, $visita);
            $distanciaProxima = $proxima ? $this->distanciaEntrePontos($visita, $proxima) : 0.0;
            $distanciaDireta = $proxima ? $this->distanciaEntrePontos($anterior, $proxima) : 0.0;
            $desvio = max(0, $distanciaAnterior + $distanciaProxima - $distanciaDireta);
            $distanciaSemParada = max(0.01, $distanciaTotal - $desvio);
            $percentual = ($desvio / $distanciaSemParada) * 100;

            $visitas[$indice]['desvio_relevante'] = $percentual > 30;
            $visitas[$indice]['motivo_desvio'] = $percentual > 30
                ? sprintf('A parada adiciona %.2f km ao trajeto atual, elevando a rota em %.2f%%.', $desvio, $percentual)
                : null;
        }

        return $visitas;
    }

    private function montarAgrupamentos(array $visitas): array
    {
        $agrupamentos = [];

        foreach ($visitas as $visita) {
            $clusterId = (int) ($visita['cluster_id'] ?? 0);
            if (!isset($agrupamentos[$clusterId])) {
                $agrupamentos[$clusterId] = [
                    'cluster_id' => $clusterId,
                    'total_visitas' => 0,
                    'prioridades' => [],
                    'primeira_ordem' => (int) ($visita['ordem_sugerida'] ?? 0),
                    'ultima_ordem' => (int) ($visita['ordem_sugerida'] ?? 0),
                    'distancia_total_km' => 0.0,
                ];
            }

            $agrupamentos[$clusterId]['total_visitas']++;
            $agrupamentos[$clusterId]['prioridades'][] = (string) ($visita['prioridade'] ?? 'media');
            $agrupamentos[$clusterId]['primeira_ordem'] = min($agrupamentos[$clusterId]['primeira_ordem'], (int) ($visita['ordem_sugerida'] ?? 0));
            $agrupamentos[$clusterId]['ultima_ordem'] = max($agrupamentos[$clusterId]['ultima_ordem'], (int) ($visita['ordem_sugerida'] ?? 0));
            $agrupamentos[$clusterId]['distancia_total_km'] += (float) ($visita['distancia_incremental_km'] ?? 0);
        }

        foreach ($agrupamentos as &$agrupamento) {
            $agrupamento['distancia_total_km'] = round($agrupamento['distancia_total_km'], 2);
            $agrupamento['prioridade_dominante'] = $this->prioridadeDominante($agrupamento['prioridades']);
            unset($agrupamento['prioridades']);
        }
        unset($agrupamento);

        ksort($agrupamentos);

        return array_values($agrupamentos);
    }

    private function gerarResumo(array $origem, array $agrupamentos, array $economia, array $visitasDesvio): string
    {
        $totalVisitas = array_sum(array_map(
            fn (array $grupo): int => (int) ($grupo['total_visitas'] ?? 0),
            $agrupamentos
        ));

        if ($totalVisitas <= 1) {
            return sprintf(
                'Saia de %s. Há apenas 1 visita no roteiro do dia, então não existe ganho de ordenação entre paradas; o foco passa a ser validar endereço e janela de horário.',
                $origem['nome'] ?? 'saída planejada'
            );
        }

        $totalClusters = count($agrupamentos);
        $clusterInicial = $agrupamentos[0]['cluster_id'] ?? 1;
        $desvios = count($visitasDesvio);
        $ganhoOperacional = $this->descreverGanhoOperacional($economia);

        $economiaResumo = $economia['economia_km'] > 0
            ? sprintf(
                'A sugestão economiza %.2f km (%.2f%%) sobre a sequência simples%s.',
                $economia['economia_km'],
                $economia['economia_percentual'],
                $ganhoOperacional !== '' ? ', ' . $ganhoOperacional : ''
            )
            : (
                $ganhoOperacional !== ''
                    ? sprintf(
                        'A quilometragem fica muito próxima da sequência simples, mas a ordem %s.',
                        $ganhoOperacional
                    )
                    : 'A sugestão mantém desempenho semelhante à sequência simples neste cenário curto.'
            );

        return sprintf(
            'Saia de %s, comece pelo cluster %d e concentre a operação em %d agrupamento(s). %s Há %d parada(s) com desvio relevante.',
            $origem['nome'] ?? 'saída planejada',
            $clusterInicial,
            $totalClusters,
            $economiaResumo,
            $desvios
        );
    }

    private function descreverEconomia(array $economia): string
    {
        $ganhoOperacional = $this->descreverGanhoOperacional($economia);

        if ($economia['economia_km'] > 0) {
            return sprintf(
                'A rota otimizada economizou %.2f km (%.2f%%)%s em relação à sequência simples de entrada.',
                $economia['economia_km'],
                $economia['economia_percentual'],
                $ganhoOperacional !== '' ? ', ' . $ganhoOperacional : ''
            );
        }

        if ($ganhoOperacional !== '') {
            return sprintf(
                'A rota manteve quilometragem semelhante à sequência simples, mas %s.',
                $ganhoOperacional
            );
        }

        return 'A rota otimizada teve desempenho muito próximo da sequência simples neste cenário.';
    }

    private function descreverGanhoOperacional(array $economia): string
    {
        $partes = [];

        if ($economia['economia_tempo_min'] > 0) {
            $partes[] = sprintf('reduz %d min', $economia['economia_tempo_min']);
        }

        if ($economia['economia_custo_estimado'] > 0) {
            $partes[] = sprintf('preserva R$ %.2f em custo estimado', $economia['economia_custo_estimado']);
        }

        return implode(' e ', $partes);
    }

    private function avaliarCluster(array $cluster, array $pontoAtual, DateTimeImmutable $tempoAtual): array
    {
        $centroide = $cluster['centroide'];
        $distancia = $this->distanciaEntrePontos($pontoAtual, $centroide);
        $prioridadeBonus = 0.0;
        $urgenciaBonus = 0.0;

        foreach ($cluster['visitas'] as $visita) {
            $prioridadeBonus = max($prioridadeBonus, $this->bonusPrioridade((string) ($visita['prioridade'] ?? 'media')));
            $fim = $this->parseDataHora($visita['horario_fim'] ?? null);
            if ($fim) {
                $minutosRestantes = $this->diferencaMinutos($tempoAtual, $fim);
                if ($tempoAtual <= $fim && $minutosRestantes <= 120) {
                    $urgenciaBonus = max($urgenciaBonus, 1.5);
                }
            }
        }

        $score = $distancia - $prioridadeBonus - $urgenciaBonus;

        return [
            'score' => $score,
            'motivo' => sprintf('está a %.2f km do ponto atual e reúne visitas com prioridade dominante %s', $distancia, $this->prioridadeDominante(array_column($cluster['visitas'], 'prioridade'))),
        ];
    }

    private function avaliarVisita(array $visita, array $pontoAtual, DateTimeImmutable $tempoAtual, string $transporte): array
    {
        $distancia = $this->distanciaEntrePontos($pontoAtual, $visita);
        $tempoDeslocamento = $this->estimarTempoDeslocamento($distancia, $transporte);
        $chegada = $tempoAtual->modify(sprintf('+%d minutes', $tempoDeslocamento));
        $janelaInicio = $this->parseDataHora($visita['horario_inicio'] ?? null);
        $janelaFim = $this->parseDataHora($visita['horario_fim'] ?? null);
        $espera = 0;
        $atraso = 0;

        if ($janelaInicio && $chegada < $janelaInicio) {
            $espera = $this->diferencaMinutos($chegada, $janelaInicio);
        }

        if ($janelaFim && $chegada > $janelaFim) {
            $atraso = $this->diferencaMinutos($janelaFim, $chegada);
        }

        $prioridadeBonus = $this->bonusPrioridade((string) ($visita['prioridade'] ?? 'media'));
        $urgenciaBonus = 0.0;
        if ($janelaFim && $tempoAtual <= $janelaFim) {
            $minutosRestantes = $this->diferencaMinutos($tempoAtual, $janelaFim);
            if ($minutosRestantes <= 90) {
                $urgenciaBonus = 1.25;
            }
        }

        $score = $distancia + ($espera / 30) + ($atraso * 5) - $prioridadeBonus - $urgenciaBonus;

        $motivos = [sprintf('combina deslocamento de %.2f km', $distancia)];
        if ($prioridadeBonus > 0) {
            $motivos[] = sprintf('prioridade %s', $visita['prioridade']);
        }
        if ($janelaInicio || $janelaFim) {
            $motivos[] = 'janela de horário monitorada';
        }

        return [
            'score' => $score,
            'motivo' => implode(', ', $motivos),
        ];
    }

    private function indexarClusters(array $visitas): array
    {
        $clusters = [];

        foreach ($visitas as $visita) {
            $clusterId = (int) ($visita['cluster_id'] ?? 0);
            if (!isset($clusters[$clusterId])) {
                $clusters[$clusterId] = [
                    'id' => $clusterId,
                    'visitas' => [],
                    'centroide' => ['latitude' => 0.0, 'longitude' => 0.0],
                ];
            }

            $clusters[$clusterId]['visitas'][] = $visita;
        }

        foreach ($clusters as $clusterId => $cluster) {
            $totalLat = 0.0;
            $totalLng = 0.0;
            foreach ($cluster['visitas'] as $visita) {
                $totalLat += (float) $visita['latitude'];
                $totalLng += (float) $visita['longitude'];
            }

            $quantidade = max(1, count($cluster['visitas']));
            $clusters[$clusterId]['centroide'] = [
                'latitude' => $totalLat / $quantidade,
                'longitude' => $totalLng / $quantidade,
            ];
        }

        ksort($clusters);

        return $clusters;
    }

    private function definirHoraInicial(string $dataRoteiro, array $visitas): DateTimeImmutable
    {
        $inicioPadrao = new DateTimeImmutable($dataRoteiro . ' 08:00:00');
        $menor = null;

        foreach ($visitas as $visita) {
            $horario = $this->parseDataHora($visita['horario_inicio'] ?? null);
            if ($horario && ($menor === null || $horario < $menor)) {
                $menor = $horario;
            }
        }

        if ($menor === null) {
            return $inicioPadrao;
        }

        return $menor->modify('-15 minutes');
    }

    private function estimarTempoDeslocamento(float $distanciaKm, string $transporte): int
    {
        $velocidadeKmH = match ($transporte) {
            'moto' => 28,
            'a_pe' => 5,
            default => 32,
        };

        if ($distanciaKm <= 0) {
            return 0;
        }

        return max(1, (int) round(($distanciaKm / $velocidadeKmH) * 60));
    }

    private function estimarCusto(float $distanciaKm, string $transporte): float
    {
        return match ($transporte) {
            'moto' => round(($distanciaKm / 32) * 6.10, 2),
            'a_pe' => 0.0,
            default => round(($distanciaKm / 10) * 6.30, 2),
        };
    }

    private function duracaoVisita(string $prioridade): int
    {
        return match ($prioridade) {
            'alta' => 30,
            'baixa' => 20,
            default => 25,
        };
    }

    private function bonusPrioridade(string $prioridade): float
    {
        return match ($prioridade) {
            'alta' => 2.4,
            'media' => 1.1,
            default => 0.0,
        };
    }

    private function prioridadeDominante(array $prioridades): string
    {
        if (in_array('alta', $prioridades, true)) {
            return 'alta';
        }

        if (in_array('media', $prioridades, true)) {
            return 'media';
        }

        return 'baixa';
    }

    private function parseDataHora(?string $valor): ?DateTimeImmutable
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        return new DateTimeImmutable($valor);
    }

    private function diferencaMinutos(DateTimeImmutable $inicio, DateTimeImmutable $fim): int
    {
        return max(0, (int) floor(($fim->getTimestamp() - $inicio->getTimestamp()) / 60));
    }

    private function distanciaEntrePontos(array $origem, array $destino): float
    {
        $chave = implode(':', [
            round((float) $origem['latitude'], 7),
            round((float) $origem['longitude'], 7),
            round((float) $destino['latitude'], 7),
            round((float) $destino['longitude'], 7),
        ]);

        if (isset($this->distanceCache[$chave])) {
            return $this->distanceCache[$chave];
        }

        $lat1 = deg2rad((float) $origem['latitude']);
        $lng1 = deg2rad((float) $origem['longitude']);
        $lat2 = deg2rad((float) $destino['latitude']);
        $lng2 = deg2rad((float) $destino['longitude']);
        $dLat = $lat2 - $lat1;
        $dLng = $lng2 - $lng1;

        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distancia = 6371 * $c;

        $this->distanceCache[$chave] = $distancia;
        $this->distanceCache[implode(':', [
            round((float) $destino['latitude'], 7),
            round((float) $destino['longitude'], 7),
            round((float) $origem['latitude'], 7),
            round((float) $origem['longitude'], 7),
        ])] = $distancia;

        return $distancia;
    }
}