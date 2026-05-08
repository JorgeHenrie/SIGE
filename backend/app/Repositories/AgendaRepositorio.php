<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Banco;
use PDO;

class AgendaRepositorio
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Banco::conexao();
    }

    public function listar(int $pagina, int $limite, string $busca = '', ?string $status = null, ?string $liderId = null): array
    {
        $offset = ($pagina - 1) * $limite;
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($busca)) {
            $where .= ' AND (titulo ILIKE :busca OR lider_nome ILIKE :busca OR COALESCE(local_evento, \'\') ILIKE :busca)';
            $params[':busca'] = "%{$busca}%";
        }

        if (!empty($status)) {
            $where .= ' AND status = :status';
            $params[':status'] = $status;
        }

        if (!empty($liderId)) {
            $where .= ' AND lider_id = :lider_id';
            $params[':lider_id'] = $liderId;
        }

        $stmtTotal = $this->pdo->prepare("SELECT COUNT(*) FROM sige.vw_agenda_eventos {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $params[':limite'] = $limite;
        $params[':offset'] = $offset;

        $stmt = $this->pdo->prepare("
            SELECT * FROM sige.vw_agenda_eventos
            {$where}
            ORDER BY
                CASE status
                    WHEN 'pendente' THEN 0
                    WHEN 'aprovado' THEN 1
                    ELSE 2
                END,
                COALESCE(data_confirmada_inicio, data_solicitada_inicio) ASC,
                criado_em DESC
            LIMIT :limite OFFSET :offset
        ");
        $stmt->execute($params);

        return ['dados' => $stmt->fetchAll(), 'total' => $total];
    }

    public function buscarPorId(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sige.vw_agenda_eventos WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function criar(array $dados): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO sige.agenda_eventos (
                lider_id,
                criado_por_usuario_id,
                titulo,
                tipo,
                descricao,
                local_evento,
                data_solicitada_inicio,
                data_solicitada_fim,
                status,
                observacoes_solicitacao
            ) VALUES (
                :lider_id,
                :criado_por_usuario_id,
                :titulo,
                :tipo,
                :descricao,
                :local_evento,
                :data_solicitada_inicio,
                :data_solicitada_fim,
                :status,
                :observacoes_solicitacao
            )
            RETURNING id
        ");

        $stmt->execute([
            ':lider_id' => $dados['lider_id'],
            ':criado_por_usuario_id' => $dados['criado_por_usuario_id'] ?? null,
            ':titulo' => $dados['titulo'],
            ':tipo' => $dados['tipo'] ?? 'reuniao',
            ':descricao' => $dados['descricao'] ?? null,
            ':local_evento' => $dados['local_evento'] ?? null,
            ':data_solicitada_inicio' => $dados['data_solicitada_inicio'],
            ':data_solicitada_fim' => $dados['data_solicitada_fim'] ?? null,
            ':status' => $dados['status'] ?? 'pendente',
            ':observacoes_solicitacao' => $dados['observacoes_solicitacao'] ?? null,
        ]);

        $id = (string) $stmt->fetchColumn();
        return $this->buscarPorId($id);
    }

    public function atualizar(string $id, array $dados): ?array
    {
        $campos = [];
        $params = [':id' => $id];

        $mapeamento = [
            'lider_id' => ':lider_id',
            'titulo' => ':titulo',
            'tipo' => ':tipo',
            'descricao' => ':descricao',
            'local_evento' => ':local_evento',
            'data_solicitada_inicio' => ':data_solicitada_inicio',
            'data_solicitada_fim' => ':data_solicitada_fim',
            'data_confirmada_inicio' => ':data_confirmada_inicio',
            'data_confirmada_fim' => ':data_confirmada_fim',
            'status' => ':status',
            'observacoes_solicitacao' => ':observacoes_solicitacao',
            'observacoes_decisao' => ':observacoes_decisao',
            'decidido_por' => ':decidido_por',
            'decidido_em' => ':decidido_em',
            'criado_por_usuario_id' => ':criado_por_usuario_id',
        ];

        foreach ($mapeamento as $campo => $placeholder) {
            if (array_key_exists($campo, $dados)) {
                $campos[] = "{$campo} = {$placeholder}";
                $params[$placeholder] = $dados[$campo];
            }
        }

        if (empty($campos)) {
            return $this->buscarPorId($id);
        }

        $set = implode(', ', $campos);

        $stmt = $this->pdo->prepare("UPDATE sige.agenda_eventos SET {$set} WHERE id = :id AND excluido_em IS NULL");
        $stmt->execute($params);

        return $this->buscarPorId($id);
    }

    public function remover(string $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE sige.agenda_eventos SET excluido_em = NOW() WHERE id = :id AND excluido_em IS NULL');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}