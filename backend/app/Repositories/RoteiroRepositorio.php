<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Banco;
use PDO;
use Throwable;

class RoteiroRepositorio
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Banco::conexao();
    }

    public function listar(int $pagina, int $limite, string $busca = '', ?string $liderId = null): array
    {
        $offset = ($pagina - 1) * $limite;
        $params = [];
        $where = 'WHERE 1=1';

        if ($busca !== '') {
            $where .= " AND ("
                . "lider_nome ILIKE :busca"
                . " OR COALESCE(local_saida, '') ILIKE :busca"
                . " OR COALESCE(transporte, '') ILIKE :busca"
                . " OR COALESCE(data_roteiro::text, '') ILIKE :busca"
                . ")";
            $params[':busca'] = "%{$busca}%";
        }

        if (!empty($liderId)) {
            $where .= ' AND lider_id = :lider_id';
            $params[':lider_id'] = $liderId;
        }

        $stmtTotal = $this->pdo->prepare("SELECT COUNT(*) FROM sige.vw_roteiros_campanha {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $params[':limite'] = $limite;
        $params[':offset'] = $offset;

        $stmt = $this->pdo->prepare("\n            SELECT * FROM sige.vw_roteiros_campanha\n            {$where}\n            ORDER BY data_roteiro DESC, criado_em DESC\n            LIMIT :limite OFFSET :offset\n        ");
        $stmt->execute($params);

        return ['dados' => $stmt->fetchAll(), 'total' => $total];
    }

    public function buscarPorId(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sige.vw_roteiros_campanha WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function listarVisitas(string $roteiroId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM sige.vw_roteiro_visitas
            WHERE roteiro_id = :roteiro_id
            ORDER BY ordem_sugerida ASC, cluster_id ASC, nome ASC
        ');
        $stmt->execute([':roteiro_id' => $roteiroId]);

        return $stmt->fetchAll();
    }

    public function criar(array $roteiro, array $visitas): array
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                INSERT INTO sige.roteiros_campanha (
                    lider_id,
                    criado_por_usuario_id,
                    data_roteiro,
                    local_saida,
                    local_saida_latitude,
                    local_saida_longitude,
                    transporte,
                    status,
                    raio_cluster_km,
                    distancia_total_km,
                    tempo_total_min,
                    custo_estimado,
                    distancia_baseline_km,
                    tempo_baseline_min,
                    economia_km,
                    economia_percentual,
                    sugestao_melhor_roteiro,
                    logs_decisao_json
                ) VALUES (
                    :lider_id,
                    :criado_por_usuario_id,
                    :data_roteiro,
                    :local_saida,
                    :local_saida_latitude,
                    :local_saida_longitude,
                    :transporte,
                    :status,
                    :raio_cluster_km,
                    :distancia_total_km,
                    :tempo_total_min,
                    :custo_estimado,
                    :distancia_baseline_km,
                    :tempo_baseline_min,
                    :economia_km,
                    :economia_percentual,
                    :sugestao_melhor_roteiro,
                    :logs_decisao_json
                )
                RETURNING id
            ');

            $stmt->execute($this->mapearParametrosRoteiro($roteiro));
            $id = (string) $stmt->fetchColumn();

            $this->substituirVisitas($id, $visitas);

            $this->pdo->commit();

            return $this->buscarPorId($id) ?? [];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function atualizar(string $id, array $roteiro, array $visitas): array
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare('
                UPDATE sige.roteiros_campanha SET
                    lider_id = :lider_id,
                    criado_por_usuario_id = :criado_por_usuario_id,
                    data_roteiro = :data_roteiro,
                    local_saida = :local_saida,
                    local_saida_latitude = :local_saida_latitude,
                    local_saida_longitude = :local_saida_longitude,
                    transporte = :transporte,
                    status = :status,
                    raio_cluster_km = :raio_cluster_km,
                    distancia_total_km = :distancia_total_km,
                    tempo_total_min = :tempo_total_min,
                    custo_estimado = :custo_estimado,
                    distancia_baseline_km = :distancia_baseline_km,
                    tempo_baseline_min = :tempo_baseline_min,
                    economia_km = :economia_km,
                    economia_percentual = :economia_percentual,
                    sugestao_melhor_roteiro = :sugestao_melhor_roteiro,
                    logs_decisao_json = :logs_decisao_json
                WHERE id = :id AND excluido_em IS NULL
            ');

            $params = $this->mapearParametrosRoteiro($roteiro);
            $params[':id'] = $id;
            $stmt->execute($params);

            $this->substituirVisitas($id, $visitas);

            $this->pdo->commit();

            return $this->buscarPorId($id) ?? [];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function remover(string $id): bool
    {
        $stmt = $this->pdo->prepare('
            UPDATE sige.roteiros_campanha
            SET excluido_em = NOW()
            WHERE id = :id AND excluido_em IS NULL
        ');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    private function substituirVisitas(string $roteiroId, array $visitas): void
    {
        $stmtDelete = $this->pdo->prepare('DELETE FROM sige.roteiro_visitas WHERE roteiro_id = :roteiro_id');
        $stmtDelete->execute([':roteiro_id' => $roteiroId]);

        $stmtInsert = $this->pdo->prepare('
            INSERT INTO sige.roteiro_visitas (
                roteiro_id,
                agenda_evento_id,
                apoiador_id,
                nome,
                endereco,
                prioridade,
                horario_inicio,
                horario_fim,
                latitude,
                longitude,
                cluster_id,
                ordem_sugerida,
                desvio_relevante,
                motivo_desvio,
                distancia_incremental_km,
                tempo_incremental_min
            ) VALUES (
                :roteiro_id,
                :agenda_evento_id,
                :apoiador_id,
                :nome,
                :endereco,
                :prioridade,
                :horario_inicio,
                :horario_fim,
                :latitude,
                :longitude,
                :cluster_id,
                :ordem_sugerida,
                :desvio_relevante,
                :motivo_desvio,
                :distancia_incremental_km,
                :tempo_incremental_min
            )
        ');

        foreach ($visitas as $visita) {
            $stmtInsert->execute([
                ':roteiro_id' => $roteiroId,
                ':agenda_evento_id' => $visita['agenda_evento_id'] ?? null,
                ':apoiador_id' => $visita['apoiador_id'] ?? null,
                ':nome' => $visita['nome'],
                ':endereco' => $visita['endereco'],
                ':prioridade' => $visita['prioridade'],
                ':horario_inicio' => $visita['horario_inicio'] ?? null,
                ':horario_fim' => $visita['horario_fim'] ?? null,
                ':latitude' => $visita['latitude'],
                ':longitude' => $visita['longitude'],
                ':cluster_id' => $visita['cluster_id'] ?? 0,
                ':ordem_sugerida' => $visita['ordem_sugerida'] ?? 0,
                ':desvio_relevante' => !empty($visita['desvio_relevante']) ? 'true' : 'false',
                ':motivo_desvio' => $visita['motivo_desvio'] ?? null,
                ':distancia_incremental_km' => $visita['distancia_incremental_km'] ?? 0,
                ':tempo_incremental_min' => $visita['tempo_incremental_min'] ?? 0,
            ]);
        }
    }

    private function mapearParametrosRoteiro(array $roteiro): array
    {
        return [
            ':lider_id' => $roteiro['lider_id'],
            ':criado_por_usuario_id' => $roteiro['criado_por_usuario_id'] ?? null,
            ':data_roteiro' => $roteiro['data_roteiro'],
            ':local_saida' => $roteiro['local_saida'],
            ':local_saida_latitude' => $roteiro['local_saida_latitude'] ?? null,
            ':local_saida_longitude' => $roteiro['local_saida_longitude'] ?? null,
            ':transporte' => $roteiro['transporte'],
            ':status' => $roteiro['status'] ?? 'processado',
            ':raio_cluster_km' => $roteiro['raio_cluster_km'] ?? 3,
            ':distancia_total_km' => $roteiro['distancia_total_km'] ?? 0,
            ':tempo_total_min' => $roteiro['tempo_total_min'] ?? 0,
            ':custo_estimado' => $roteiro['custo_estimado'] ?? 0,
            ':distancia_baseline_km' => $roteiro['distancia_baseline_km'] ?? 0,
            ':tempo_baseline_min' => $roteiro['tempo_baseline_min'] ?? 0,
            ':economia_km' => $roteiro['economia_km'] ?? 0,
            ':economia_percentual' => $roteiro['economia_percentual'] ?? 0,
            ':sugestao_melhor_roteiro' => $roteiro['sugestao_melhor_roteiro'] ?? null,
            ':logs_decisao_json' => json_encode(
                $roteiro['logs_decisao_json'] ?? [],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
        ];
    }
}