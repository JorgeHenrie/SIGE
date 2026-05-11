<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Banco;
use PDO;

class LiderRepositorio
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Banco::conexao();
    }

    public function listar(int $pagina, int $limite, string $busca = ''): array
    {
        $offset = ($pagina - 1) * $limite;
        $params = [];

        $where = 'WHERE 1=1';
        if (!empty($busca)) {
            $where    .= ' AND (nome ILIKE :busca OR bairro ILIKE :busca)';
            $params[':busca'] = "%{$busca}%";
        }

        // Total
        $stmtTotal = $this->pdo->prepare("SELECT COUNT(*) FROM sige.vw_lideres_ativos {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        // Dados paginados
        $params[':limite'] = $limite;
        $params[':offset'] = $offset;

        $stmt = $this->pdo->prepare("
            SELECT * FROM sige.vw_lideres_ativos
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
            SELECT * FROM sige.vw_lideres_ativos WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function buscarPorCpfHash(string $cpfHash): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id FROM sige.lideres
            WHERE cpf_hash = :cpf_hash AND excluido_em IS NULL
        ");
        $stmt->execute([':cpf_hash' => $cpfHash]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function criar(array $dados): array
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO sige.lideres
                (nome, cpf, cpf_hash, telefone, bairro, votos_estimados, observacoes, status, criado_por)
            VALUES
                (:nome, :cpf, :cpf_hash, :telefone, :bairro, :votos_estimados, :observacoes, :status, :criado_por)
            RETURNING id
        ");
        $stmt = $this->pdo->prepare("
            INSERT INTO sige.lideres
                (nome, cpf, cpf_hash, telefone, bairro, votos_estimados, salario_mensal, observacoes, status, criado_por)
            VALUES
                (:nome, :cpf, :cpf_hash, :telefone, :bairro, :votos_estimados, :salario_mensal, :observacoes, :status, :criado_por)
            RETURNING id
        ");

        $stmt->execute([
            ':nome'            => $dados['nome'],
            ':cpf'             => $dados['cpf'],
            ':cpf_hash'        => $dados['cpf_hash'],
            ':telefone'        => $dados['telefone'] ?? null,
            ':bairro'          => $dados['bairro'] ?? null,
            ':votos_estimados' => $dados['votos_estimados'] ?? 0,
            ':salario_mensal'  => $dados['salario_mensal'] ?? null,
            ':observacoes'     => $dados['observacoes'] ?? null,
            ':status'          => isset($dados['status']) ? (bool) $dados['status'] : true,
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
            'nome'            => ':nome',
            'telefone'        => ':telefone',
            'bairro'          => ':bairro',
            'votos_estimados' => ':votos_estimados',
            'salario_mensal'  => ':salario_mensal',
            'observacoes'     => ':observacoes',
            'status'          => ':status',
        ];

        foreach ($mapeamento as $campo => $placeholder) {
            if (array_key_exists($campo, $dados)) {
                $campos[]           = "{$campo} = {$placeholder}";
                $params[$placeholder] = $dados[$campo];
            }
        }

        if (empty($campos)) {
            return $this->buscarPorId($id);
        }

        $set  = implode(', ', $campos);
        $stmt = $this->pdo->prepare("
            UPDATE sige.lideres SET {$set}
            WHERE id = :id AND excluido_em IS NULL
        ");
        $stmt->execute($params);

        return $this->buscarPorId($id);
    }

    public function remover(string $id): bool
    {
        $stmt = $this->pdo->prepare("\n            UPDATE sige.lideres\n            SET excluido_em = NOW()\n            WHERE id = :id AND excluido_em IS NULL\n        ");
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
