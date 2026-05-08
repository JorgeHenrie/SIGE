<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Banco;
use PDO;

class ApoiadorRepositorio
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

        if (!empty($liderId)) {
            $where    .= ' AND lider_id = :lider_id';
            $params[':lider_id'] = $liderId;
        }

        if (!empty($busca)) {
            $where    .= ' AND (nome ILIKE :busca OR bairro ILIKE :busca)';
            $params[':busca'] = "%{$busca}%";
        }

        // Total
        $stmtTotal = $this->pdo->prepare("SELECT COUNT(*) FROM sige.vw_apoiadores_ativos {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        // Dados paginados
        $params[':limite'] = $limite;
        $params[':offset'] = $offset;

        $stmt = $this->pdo->prepare("
            SELECT * FROM sige.vw_apoiadores_ativos
            {$where}
            ORDER BY nome ASC
            LIMIT :limite OFFSET :offset
        ");
        $stmt->execute($params);

        return ['dados' => $stmt->fetchAll(), 'total' => $total];
    }

    public function buscarPorId(string $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM sige.vw_apoiadores_ativos WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function buscarPorCpfHash(string $cpfHash): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id FROM sige.apoiadores
            WHERE cpf_hash = :cpf_hash AND excluido_em IS NULL
        ");
        $stmt->execute([':cpf_hash' => $cpfHash]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function criar(array $dados): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO sige.apoiadores
                (lider_id, nome, cpf, cpf_hash, telefone, bairro, status_politico, observacoes, criado_por)
            VALUES
                (:lider_id, :nome, :cpf, :cpf_hash, :telefone, :bairro, :status_politico, :observacoes, :criado_por)
            RETURNING id
        ");

        $stmt->execute([
            ':lider_id'        => $dados['lider_id'],
            ':nome'            => $dados['nome'],
            ':cpf'             => $dados['cpf'],
            ':cpf_hash'        => $dados['cpf_hash'],
            ':telefone'        => $dados['telefone'] ?? null,
            ':bairro'          => $dados['bairro'] ?? null,
            ':status_politico' => $dados['status_politico'] ?? 'indeciso',
            ':observacoes'     => $dados['observacoes'] ?? null,
            ':criado_por'      => $dados['criado_por'] ?? null,
        ]);

        $id = $stmt->fetchColumn();
        return $this->buscarPorId($id);
    }

    public function atualizar(string $id, array $dados): ?array
    {
        $campos = [];
        $params = [':id' => $id];

        $mapeamento = [
            'lider_id'        => ':lider_id',
            'nome'            => ':nome',
            'telefone'        => ':telefone',
            'bairro'          => ':bairro',
            'status_politico' => ':status_politico',
            'observacoes'     => ':observacoes',
        ];

        foreach ($mapeamento as $campo => $placeholder) {
            if (array_key_exists($campo, $dados)) {
                $campos[]             = "{$campo} = {$placeholder}";
                $params[$placeholder] = $dados[$campo];
            }
        }

        if (empty($campos)) {
            return $this->buscarPorId($id);
        }

        $set  = implode(', ', $campos);
        $stmt = $this->pdo->prepare("
            UPDATE sige.apoiadores SET {$set}
            WHERE id = :id AND excluido_em IS NULL
        ");
        $stmt->execute($params);

        return $this->buscarPorId($id);
    }

    public function remover(string $id): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE sige.apoiadores
            SET excluido_em = NOW()
            WHERE id = :id AND excluido_em IS NULL
        ");
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
