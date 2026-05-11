<?php

declare(strict_types=1);

namespace App\Services;

class ClusteringServico
{
    public function agrupar(array $visitas, float $raioKm = 3.0): array
    {
        $clusters = [];
        $logs = [];

        usort($visitas, function (array $a, array $b): int {
            $pesoA = $this->pesoPrioridade((string) ($a['prioridade'] ?? 'media'));
            $pesoB = $this->pesoPrioridade((string) ($b['prioridade'] ?? 'media'));

            if ($pesoA !== $pesoB) {
                return $pesoB <=> $pesoA;
            }

            $horarioA = $a['horario_inicio'] ?? '9999-12-31 23:59:59';
            $horarioB = $b['horario_inicio'] ?? '9999-12-31 23:59:59';

            if ($horarioA !== $horarioB) {
                return strcmp((string) $horarioA, (string) $horarioB);
            }

            return ((int) ($a['original_index'] ?? 0)) <=> ((int) ($b['original_index'] ?? 0));
        });

        foreach ($visitas as $visita) {
            $visitada = false;

            foreach ($clusters as &$cluster) {
                $distanciaMinima = $this->menorDistanciaParaCluster($visita, $cluster['visitas']);
                if ($distanciaMinima <= $raioKm) {
                    $visita['cluster_id'] = $cluster['id'];
                    $cluster['visitas'][] = $visita;
                    $cluster['centroide'] = $this->calcularCentroide($cluster['visitas']);
                    $logs[] = [
                        'tipo' => 'clusterizacao',
                        'mensagem' => sprintf(
                            'A visita "%s" foi agrupada no cluster %d por estar a %.2f km do grupo.',
                            $visita['nome'],
                            $cluster['id'],
                            $distanciaMinima
                        ),
                    ];
                    $visitada = true;
                    break;
                }
            }
            unset($cluster);

            if ($visitada) {
                continue;
            }

            $clusterId = count($clusters) + 1;
            $visita['cluster_id'] = $clusterId;
            $clusters[] = [
                'id' => $clusterId,
                'visitas' => [$visita],
                'centroide' => $this->calcularCentroide([$visita]),
            ];
            $logs[] = [
                'tipo' => 'clusterizacao',
                'mensagem' => sprintf(
                    'A visita "%s" iniciou o cluster %d por estar fora do raio de %.1f km dos grupos existentes.',
                    $visita['nome'],
                    $clusterId,
                    $raioKm
                ),
            ];
        }

        $visitasAgrupadas = [];
        foreach ($clusters as $cluster) {
            foreach ($cluster['visitas'] as $visita) {
                $visitasAgrupadas[] = $visita;
            }
        }

        return [
            'visitas' => $visitasAgrupadas,
            'clusters' => $clusters,
            'logs' => $logs,
        ];
    }

    private function menorDistanciaParaCluster(array $visita, array $visitasCluster): float
    {
        $menor = INF;

        foreach ($visitasCluster as $item) {
            $distancia = $this->distanciaKm(
                (float) $visita['latitude'],
                (float) $visita['longitude'],
                (float) $item['latitude'],
                (float) $item['longitude']
            );

            if ($distancia < $menor) {
                $menor = $distancia;
            }
        }

        return $menor;
    }

    private function calcularCentroide(array $visitas): array
    {
        $totalLat = 0.0;
        $totalLng = 0.0;

        foreach ($visitas as $visita) {
            $totalLat += (float) $visita['latitude'];
            $totalLng += (float) $visita['longitude'];
        }

        $quantidade = max(1, count($visitas));

        return [
            'latitude' => $totalLat / $quantidade,
            'longitude' => $totalLng / $quantidade,
        ];
    }

    private function pesoPrioridade(string $prioridade): int
    {
        return match ($prioridade) {
            'alta' => 3,
            'media' => 2,
            default => 1,
        };
    }

    private function distanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $raioTerra = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $raioTerra * $c;
    }
}