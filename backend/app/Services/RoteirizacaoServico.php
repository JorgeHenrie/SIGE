<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Excecoes\AutorizacaoException;
use App\Core\Excecoes\NaoEncontradoException;
use App\Core\Excecoes\ValidacaoException;
use App\Repositories\AgendaRepositorio;
use App\Repositories\ApoiadorRepositorio;
use App\Repositories\LiderRepositorio;
use App\Repositories\RoteiroRepositorio;
use App\Validators\RoteiroValidador;

class RoteirizacaoServico
{
    private const DISTANCIA_MAXIMA_VISITA_PADRAO_KM = 120.0;

    private RoteiroRepositorio $repositorio;
    private LiderRepositorio $liderRepositorio;
    private AgendaRepositorio $agendaRepositorio;
    private ApoiadorRepositorio $apoiadorRepositorio;
    private GeocodingServico $geocodingServico;
    private ClusteringServico $clusteringServico;
    private RoutingServico $routingServico;

    public function __construct()
    {
        $this->repositorio = new RoteiroRepositorio();
        $this->liderRepositorio = new LiderRepositorio();
        $this->agendaRepositorio = new AgendaRepositorio();
        $this->apoiadorRepositorio = new ApoiadorRepositorio();
        $this->geocodingServico = new GeocodingServico();
        $this->clusteringServico = new ClusteringServico();
        $this->routingServico = new RoutingServico();
    }

    public function listar(int $pagina, int $limite, string $busca, ?string $liderId, array $auth): array
    {
        $pagina = max(1, $pagina);
        $limite = min(100, max(1, $limite));

        $liderIdFiltro = $this->perfilEhLider($auth)
            ? (string) ($auth['sub'] ?? '')
            : ($liderId ?: null);

        $resultado = $this->repositorio->listar($pagina, $limite, $busca, $liderIdFiltro);
        $resultado['dados'] = array_map(fn (array $roteiro): array => $this->enriquecerEconomiaResumo($roteiro), $resultado['dados']);

        return $resultado;
    }

    public function buscarPorId(string $id, array $auth): array
    {
        $roteiro = $this->repositorio->buscarPorId($id);

        if (!$roteiro) {
            throw new NaoEncontradoException('Roteiro');
        }

        $this->garantirAcessoAoRoteiro($roteiro, $auth);

        return $this->montarRespostaPersistida($roteiro);
    }

    public function sugerir(array $dados, array $auth): array
    {
        $payload = $this->prepararPlanejamento($dados, $auth);
        return $this->gerarRoteiro($payload);
    }

    public function cadastrar(array $dados, array $auth): array
    {
        if (!$this->podeCriarRoteiro($auth)) {
            throw new AutorizacaoException('Apenas líderes, gestores e administradores podem criar roteiros.');
        }

        $payload = $this->prepararPlanejamento($dados, $auth);
        $resultado = $this->gerarRoteiro($payload);

        $persistido = $this->repositorio->criar($this->mapearRoteiroPersistencia($resultado), $resultado['visitas']);

        return $this->montarRespostaPersistida($persistido);
    }

    public function recalcular(string $id, array $dados, array $auth): array
    {
        if (!$this->podeCriarRoteiro($auth)) {
            throw new AutorizacaoException('Apenas líderes, gestores e administradores podem recalcular roteiros.');
        }

        $existente = $this->buscarPorId($id, $auth);
        $visitas = $dados['visitas'] ?? $this->visitasParaReprocessamento($existente['visitas'] ?? []);

        $payload = [
            'lider_id' => $dados['lider_id'] ?? $existente['lider_id'],
            'data_roteiro' => $dados['data_roteiro'] ?? $existente['data_roteiro'],
            'local_saida' => $dados['local_saida'] ?? $existente['local_saida'],
            'local_saida_latitude' => $dados['local_saida_latitude'] ?? $existente['local_saida_latitude'],
            'local_saida_longitude' => $dados['local_saida_longitude'] ?? $existente['local_saida_longitude'],
            'transporte' => $dados['transporte'] ?? $existente['transporte'],
            'raio_cluster_km' => $dados['raio_cluster_km'] ?? $existente['raio_cluster_km'],
            'visitas' => $visitas,
        ];

        $planejamento = $this->prepararPlanejamento($payload, $auth);
        $resultado = $this->gerarRoteiro($planejamento);

        $persistido = $this->repositorio->atualizar($id, $this->mapearRoteiroPersistencia($resultado), $resultado['visitas']);

        return $this->montarRespostaPersistida($persistido);
    }

    public function remover(string $id, array $auth): void
    {
        $roteiro = $this->repositorio->buscarPorId($id);

        if (!$roteiro) {
            throw new NaoEncontradoException('Roteiro');
        }

        $this->garantirAcessoAoRoteiro($roteiro, $auth);

        if (!$this->repositorio->remover($id)) {
            throw new NaoEncontradoException('Roteiro');
        }
    }

    private function prepararPlanejamento(array $dados, array $auth): array
    {
        $dados['lider_id'] = $this->resolverLiderId($dados, $auth);

        $erros = RoteiroValidador::validarPlanejamento($dados);
        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        if (!$this->liderRepositorio->buscarPorId((string) $dados['lider_id'])) {
            throw new NaoEncontradoException('Líder');
        }

        try {
            $origem = $this->geocodingServico->resolverCoordenadas(
                (string) $dados['local_saida'],
                $dados['local_saida_latitude'] ?? null,
                $dados['local_saida_longitude'] ?? null
            );
        } catch (\Throwable $e) {
            throw new ValidacaoException(['local_saida' => $e->getMessage()]);
        }

        $visitas = [];
        $errosVisitas = [];
        foreach ($dados['visitas'] as $indice => $visita) {
            try {
                $visitas[] = $this->resolverVisita($visita, (string) $dados['lider_id'], $indice);
            } catch (ValidacaoException $e) {
                foreach ($e->getErros() as $chave => $mensagem) {
                    $errosVisitas[is_string($chave) ? $chave : "visitas.{$indice}"] = $mensagem;
                }
            } catch (\Throwable $e) {
                $errosVisitas["visitas.{$indice}"] = $e->getMessage();
            }
        }

        if (!empty($errosVisitas)) {
            throw new ValidacaoException($errosVisitas);
        }

        $this->validarDistanciasDaOrigem($origem, $visitas);

        return [
            'lider_id' => (string) $dados['lider_id'],
            'criado_por_usuario_id' => $this->perfilEhLider($auth) ? null : ($auth['sub'] ?? null),
            'data_roteiro' => (new \DateTimeImmutable((string) $dados['data_roteiro']))->format('Y-m-d'),
            'local_saida' => trim((string) $dados['local_saida']),
            'local_saida_latitude' => $origem['latitude'],
            'local_saida_longitude' => $origem['longitude'],
            'transporte' => (string) $dados['transporte'],
            'raio_cluster_km' => round((float) ($dados['raio_cluster_km'] ?? 3), 2),
            'visitas' => $visitas,
            'status' => 'processado',
        ];
    }

    private function validarDistanciasDaOrigem(array $origem, array $visitas): void
    {
        $limite = (float) ($_ENV['ROUTE_MAX_VISITA_DISTANCIA_KM'] ?? self::DISTANCIA_MAXIMA_VISITA_PADRAO_KM);
        $limite = max(10.0, $limite);
        $erros = [];

        foreach ($visitas as $indice => $visita) {
            $distanciaKm = $this->calcularDistanciaKm(
                (float) $origem['latitude'],
                (float) $origem['longitude'],
                (float) $visita['latitude'],
                (float) $visita['longitude']
            );

            if ($distanciaKm > $limite) {
                $erros["visitas.{$indice}.endereco"] = sprintf(
                    'A visita "%s" ficou a %.2f km da origem. Complete o endereço com número, cidade e UF para um geocoding preciso.',
                    (string) ($visita['nome'] ?? 'sem nome'),
                    $distanciaKm
                );
            }
        }

        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }
    }

    private function calcularDistanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $raioTerraKm = 6371.0;
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $raioTerraKm * $c;
    }

    private function resolverVisita(array $visita, string $liderId, int $indice): array
    {
        $agenda = null;
        $apoiador = null;

        if (!empty($visita['agenda_evento_id'])) {
            $agenda = $this->agendaRepositorio->buscarPorId((string) $visita['agenda_evento_id']);
            if (!$agenda) {
                throw new ValidacaoException(["visitas.{$indice}.agenda_evento_id" => 'Evento de agenda não encontrado.']);
            }

            if (($agenda['lider_id'] ?? null) !== $liderId) {
                throw new ValidacaoException(["visitas.{$indice}.agenda_evento_id" => 'O evento de agenda deve pertencer ao mesmo líder do roteiro.']);
            }
        }

        if (!empty($visita['apoiador_id'])) {
            $apoiador = $this->apoiadorRepositorio->buscarPorId((string) $visita['apoiador_id']);
            if (!$apoiador) {
                throw new ValidacaoException(["visitas.{$indice}.apoiador_id" => 'Apoiador não encontrado.']);
            }

            if (($apoiador['lider_id'] ?? null) !== $liderId) {
                throw new ValidacaoException(["visitas.{$indice}.apoiador_id" => 'O apoiador deve pertencer ao mesmo líder do roteiro.']);
            }
        }

        $nome = trim((string) ($visita['nome'] ?? $agenda['titulo'] ?? $apoiador['nome'] ?? ''));
        $endereco = trim((string) ($visita['endereco'] ?? $agenda['local_evento'] ?? ''));

        if ($nome === '') {
            throw new ValidacaoException(["visitas.{$indice}.nome" => 'Não foi possível resolver o nome da visita.']);
        }

        if ($endereco === '') {
            throw new ValidacaoException(["visitas.{$indice}.endereco" => 'Informe um endereço completo para a visita.']);
        }

        try {
            $coordenadas = $this->geocodingServico->resolverCoordenadas(
                $endereco,
                $visita['latitude'] ?? null,
                $visita['longitude'] ?? null
            );
        } catch (\Throwable $e) {
            throw new ValidacaoException(["visitas.{$indice}.endereco" => $e->getMessage()]);
        }

        return [
            'agenda_evento_id' => $visita['agenda_evento_id'] ?? null,
            'apoiador_id' => $visita['apoiador_id'] ?? null,
            'nome' => $nome,
            'endereco' => $endereco,
            'prioridade' => (string) ($visita['prioridade'] ?? 'media'),
            'horario_inicio' => $this->normalizarDataHora($visita['horario_inicio'] ?? $agenda['data_confirmada_inicio'] ?? $agenda['data_solicitada_inicio'] ?? null),
            'horario_fim' => $this->normalizarDataHora($visita['horario_fim'] ?? $agenda['data_confirmada_fim'] ?? $agenda['data_solicitada_fim'] ?? null),
            'latitude' => $coordenadas['latitude'],
            'longitude' => $coordenadas['longitude'],
            'cluster_id' => 0,
            'ordem_sugerida' => 0,
            'desvio_relevante' => false,
            'motivo_desvio' => null,
            'distancia_incremental_km' => 0,
            'tempo_incremental_min' => 0,
            'original_index' => $indice,
        ];
    }

    private function gerarRoteiro(array $payload): array
    {
        $clusterizacao = $this->clusteringServico->agrupar($payload['visitas'], (float) $payload['raio_cluster_km']);
        $roteamento = $this->routingServico->processar(
            $clusterizacao['visitas'],
            [
                'nome' => $payload['local_saida'],
                'latitude' => $payload['local_saida_latitude'],
                'longitude' => $payload['local_saida_longitude'],
            ],
            $payload['transporte'],
            $payload['data_roteiro']
        );

        return [
            'id' => null,
            'lider_id' => $payload['lider_id'],
            'criado_por_usuario_id' => $payload['criado_por_usuario_id'],
            'data_roteiro' => $payload['data_roteiro'],
            'local_saida' => $payload['local_saida'],
            'local_saida_latitude' => $payload['local_saida_latitude'],
            'local_saida_longitude' => $payload['local_saida_longitude'],
            'transporte' => $payload['transporte'],
            'status' => 'processado',
            'raio_cluster_km' => $payload['raio_cluster_km'],
            'distancia_total_km' => $roteamento['distancia_total_km'],
            'tempo_total_min' => $roteamento['tempo_total_min'],
            'custo_estimado' => $roteamento['custo_estimado'],
            'distancia_baseline_km' => $roteamento['distancia_baseline_km'],
            'tempo_baseline_min' => $roteamento['tempo_baseline_min'],
            'custo_baseline_estimado' => $roteamento['custo_baseline_estimado'],
            'economia_km' => $roteamento['economia_km'],
            'economia_percentual' => $roteamento['economia_percentual'],
            'economia_tempo_min' => $roteamento['economia_tempo_min'],
            'economia_tempo_percentual' => $roteamento['economia_tempo_percentual'],
            'economia_custo_estimado' => $roteamento['economia_custo_estimado'],
            'sugestao_melhor_roteiro' => $roteamento['sugestao_melhor_roteiro'],
            'logs_decisao' => array_merge($clusterizacao['logs'], $roteamento['logs_decisao']),
            'logs_decisao_json' => array_merge($clusterizacao['logs'], $roteamento['logs_decisao']),
            'agrupamentos' => $roteamento['agrupamentos'],
            'visitas_desvio' => $roteamento['visitas_desvio'],
            'visitas' => $roteamento['visitas'],
        ];
    }

    private function montarRespostaPersistida(array $roteiro): array
    {
        $visitasBase = $this->repositorio->listarVisitas((string) $roteiro['id']);
        $visitas = $this->normalizarVisitasPersistidas($visitasBase);
        $simulacao = $this->routingServico->enriquecerSequenciaPersistida(
            $visitas,
            [
                'nome' => $roteiro['local_saida'],
                'latitude' => $roteiro['local_saida_latitude'],
                'longitude' => $roteiro['local_saida_longitude'],
            ],
            (string) $roteiro['transporte'],
            (string) $roteiro['data_roteiro']
        );
        $economia = $this->routingServico->calcularEconomiaComparativa(
            isset($roteiro['distancia_baseline_km']) ? (float) $roteiro['distancia_baseline_km'] : 0.0,
            (int) ($roteiro['tempo_baseline_min'] ?? 0),
            isset($roteiro['distancia_total_km']) ? (float) $roteiro['distancia_total_km'] : 0.0,
            (int) ($roteiro['tempo_total_min'] ?? 0),
            (string) $roteiro['transporte']
        );

        return [
            'id' => $roteiro['id'],
            'lider_id' => $roteiro['lider_id'],
            'lider_nome' => $roteiro['lider_nome'] ?? null,
            'lider_bairro' => $roteiro['lider_bairro'] ?? null,
            'criado_por_usuario_id' => $roteiro['criado_por_usuario_id'] ?? null,
            'criado_por_usuario_nome' => $roteiro['criado_por_usuario_nome'] ?? null,
            'data_roteiro' => $roteiro['data_roteiro'],
            'local_saida' => $roteiro['local_saida'],
            'local_saida_latitude' => isset($roteiro['local_saida_latitude']) ? (float) $roteiro['local_saida_latitude'] : null,
            'local_saida_longitude' => isset($roteiro['local_saida_longitude']) ? (float) $roteiro['local_saida_longitude'] : null,
            'transporte' => $roteiro['transporte'],
            'status' => $roteiro['status'],
            'raio_cluster_km' => isset($roteiro['raio_cluster_km']) ? (float) $roteiro['raio_cluster_km'] : 3.0,
            'distancia_total_km' => isset($roteiro['distancia_total_km']) ? (float) $roteiro['distancia_total_km'] : 0.0,
            'tempo_total_min' => (int) ($roteiro['tempo_total_min'] ?? 0),
            'custo_estimado' => isset($roteiro['custo_estimado']) ? (float) $roteiro['custo_estimado'] : 0.0,
            'distancia_baseline_km' => isset($roteiro['distancia_baseline_km']) ? (float) $roteiro['distancia_baseline_km'] : 0.0,
            'tempo_baseline_min' => (int) ($roteiro['tempo_baseline_min'] ?? 0),
            'custo_baseline_estimado' => $economia['custo_baseline_estimado'],
            'economia_km' => $economia['economia_km'],
            'economia_percentual' => $economia['economia_percentual'],
            'economia_tempo_min' => $economia['economia_tempo_min'],
            'economia_tempo_percentual' => $economia['economia_tempo_percentual'],
            'economia_custo_estimado' => $economia['economia_custo_estimado'],
            'total_visitas' => (int) ($roteiro['total_visitas'] ?? count($visitas)),
            'sugestao_melhor_roteiro' => $roteiro['sugestao_melhor_roteiro'] ?? null,
            'logs_decisao' => $this->decodificarLogs($roteiro['logs_decisao_json'] ?? []),
            'agrupamentos' => $this->montarAgrupamentos($simulacao['visitas']),
            'visitas_desvio' => array_values(array_filter($simulacao['visitas'], fn (array $visita): bool => !empty($visita['desvio_relevante']))),
            'visitas' => $simulacao['visitas'],
            'criado_em' => $roteiro['criado_em'] ?? null,
            'atualizado_em' => $roteiro['atualizado_em'] ?? null,
        ];
    }

    private function mapearRoteiroPersistencia(array $resultado): array
    {
        return [
            'lider_id' => $resultado['lider_id'],
            'criado_por_usuario_id' => $resultado['criado_por_usuario_id'],
            'data_roteiro' => $resultado['data_roteiro'],
            'local_saida' => $resultado['local_saida'],
            'local_saida_latitude' => $resultado['local_saida_latitude'],
            'local_saida_longitude' => $resultado['local_saida_longitude'],
            'transporte' => $resultado['transporte'],
            'status' => $resultado['status'],
            'raio_cluster_km' => $resultado['raio_cluster_km'],
            'distancia_total_km' => $resultado['distancia_total_km'],
            'tempo_total_min' => $resultado['tempo_total_min'],
            'custo_estimado' => $resultado['custo_estimado'],
            'distancia_baseline_km' => $resultado['distancia_baseline_km'],
            'tempo_baseline_min' => $resultado['tempo_baseline_min'],
            'economia_km' => $resultado['economia_km'],
            'economia_percentual' => $resultado['economia_percentual'],
            'sugestao_melhor_roteiro' => $resultado['sugestao_melhor_roteiro'],
            'logs_decisao_json' => $resultado['logs_decisao_json'],
        ];
    }

    private function enriquecerEconomiaResumo(array $roteiro): array
    {
        $economia = $this->routingServico->calcularEconomiaComparativa(
            isset($roteiro['distancia_baseline_km']) ? (float) $roteiro['distancia_baseline_km'] : 0.0,
            (int) ($roteiro['tempo_baseline_min'] ?? 0),
            isset($roteiro['distancia_total_km']) ? (float) $roteiro['distancia_total_km'] : 0.0,
            (int) ($roteiro['tempo_total_min'] ?? 0),
            (string) ($roteiro['transporte'] ?? 'carro')
        );

        return array_merge($roteiro, $economia);
    }

    private function visitasParaReprocessamento(array $visitas): array
    {
        $resultado = [];

        foreach ($visitas as $visita) {
            $resultado[] = [
                'agenda_evento_id' => $visita['agenda_evento_id'] ?? null,
                'apoiador_id' => $visita['apoiador_id'] ?? null,
                'nome' => $visita['nome'] ?? '',
                'endereco' => $visita['endereco'] ?? '',
                'prioridade' => $visita['prioridade'] ?? 'media',
                'horario_inicio' => $visita['horario_inicio'] ?? null,
                'horario_fim' => $visita['horario_fim'] ?? null,
                'latitude' => $visita['latitude'] ?? null,
                'longitude' => $visita['longitude'] ?? null,
            ];
        }

        return $resultado;
    }

    private function normalizarVisitasPersistidas(array $visitas): array
    {
        $normalizadas = [];

        foreach ($visitas as $indice => $visita) {
            $visita['latitude'] = isset($visita['latitude']) ? (float) $visita['latitude'] : null;
            $visita['longitude'] = isset($visita['longitude']) ? (float) $visita['longitude'] : null;
            $visita['cluster_id'] = (int) ($visita['cluster_id'] ?? 0);
            $visita['ordem_sugerida'] = (int) ($visita['ordem_sugerida'] ?? 0);
            $visita['distancia_incremental_km'] = isset($visita['distancia_incremental_km']) ? (float) $visita['distancia_incremental_km'] : 0.0;
            $visita['tempo_incremental_min'] = (int) ($visita['tempo_incremental_min'] ?? 0);
            $visita['desvio_relevante'] = in_array($visita['desvio_relevante'] ?? false, [true, 't', 'true', 1, '1'], true);
            $visita['original_index'] = $indice;
            $normalizadas[] = $visita;
        }

        return $normalizadas;
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
                    'prioridade_dominante' => $visita['prioridade'] ?? 'media',
                    'primeira_ordem' => (int) ($visita['ordem_sugerida'] ?? 0),
                    'ultima_ordem' => (int) ($visita['ordem_sugerida'] ?? 0),
                    'distancia_total_km' => 0.0,
                ];
            }

            $agrupamentos[$clusterId]['total_visitas']++;
            $agrupamentos[$clusterId]['distancia_total_km'] += (float) ($visita['distancia_incremental_km'] ?? 0);
            $agrupamentos[$clusterId]['primeira_ordem'] = min($agrupamentos[$clusterId]['primeira_ordem'], (int) ($visita['ordem_sugerida'] ?? 0));
            $agrupamentos[$clusterId]['ultima_ordem'] = max($agrupamentos[$clusterId]['ultima_ordem'], (int) ($visita['ordem_sugerida'] ?? 0));

            if (($visita['prioridade'] ?? 'media') === 'alta') {
                $agrupamentos[$clusterId]['prioridade_dominante'] = 'alta';
            } elseif (
                ($visita['prioridade'] ?? 'media') === 'media'
                && $agrupamentos[$clusterId]['prioridade_dominante'] !== 'alta'
            ) {
                $agrupamentos[$clusterId]['prioridade_dominante'] = 'media';
            }
        }

        foreach ($agrupamentos as &$agrupamento) {
            $agrupamento['distancia_total_km'] = round($agrupamento['distancia_total_km'], 2);
        }
        unset($agrupamento);

        ksort($agrupamentos);
        return array_values($agrupamentos);
    }

    private function garantirAcessoAoRoteiro(array $roteiro, array $auth): void
    {
        if ($this->perfilEhLider($auth) && ($roteiro['lider_id'] ?? null) !== ($auth['sub'] ?? null)) {
            throw new AutorizacaoException('Você não pode acessar roteiros de outro líder.');
        }
    }

    private function resolverLiderId(array $dados, array $auth): string
    {
        if ($this->perfilEhLider($auth)) {
            return (string) ($auth['sub'] ?? '');
        }

        return (string) ($dados['lider_id'] ?? '');
    }

    private function podeCriarRoteiro(array $auth): bool
    {
        return $this->perfilEhLider($auth) || $this->podeGerenciarTodos($auth);
    }

    private function podeGerenciarTodos(array $auth): bool
    {
        return in_array($auth['perfil'] ?? '', ['admin', 'gestor'], true);
    }

    private function perfilEhLider(array $auth): bool
    {
        return ($auth['perfil'] ?? null) === 'lider' && ($auth['tipo'] ?? null) === 'lider';
    }

    private function normalizarDataHora(?string $valor): ?string
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        return (new \DateTimeImmutable($valor))->format('Y-m-d H:i:s');
    }

    private function decodificarLogs(mixed $valor): array
    {
        if (is_array($valor)) {
            return $valor;
        }

        if (is_string($valor) && trim($valor) !== '') {
            $dados = json_decode($valor, true);
            return is_array($dados) ? $dados : [];
        }

        return [];
    }
}