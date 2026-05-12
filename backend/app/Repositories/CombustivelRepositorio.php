<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Banco;
use PDO;

class CombustivelRepositorio
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

        if (!empty($busca)) {
            $where .= " AND ("
                . "lider_nome ILIKE :busca"
                . " OR placa_veiculo ILIKE :busca"
                . " OR COALESCE(veiculo_descricao, '') ILIKE :busca"
                . " OR COALESCE(tipo_combustivel, '') ILIKE :busca"
                . " OR COALESCE(motorista_nome, '') ILIKE :busca"
                . " OR COALESCE(local_abastecimento, '') ILIKE :busca"
                . " OR COALESCE(odometro_atual::text, '') ILIKE :busca"
                . " OR COALESCE(finalidade, '') ILIKE :busca"
                . " OR COALESCE(numero_nota_fiscal, '') ILIKE :busca"
                . " OR COALESCE(observacoes, '') ILIKE :busca"
                . ")";
            $params[':busca'] = "%{$busca}%";
        }

        if (!empty($liderId)) {
            $where .= ' AND lider_id = :lider_id';
            $params[':lider_id'] = $liderId;
        }

        $stmtTotal = $this->pdo->prepare("SELECT COUNT(*) FROM sige.vw_combustivel_abastecimentos {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $params[':limite'] = $limite;
        $params[':offset'] = $offset;

        $stmt = $this->pdo->prepare("\n            SELECT * FROM sige.vw_combustivel_abastecimentos\n            {$where}\n            ORDER BY data_abastecimento DESC, criado_em DESC\n            LIMIT :limite OFFSET :offset\n        ");
        $stmt->execute($params);

        return ['dados' => $stmt->fetchAll(), 'total' => $total];
    }

    public function buscarPorId(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sige.vw_combustivel_abastecimentos WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function criar(array $dados): array
    {
        $stmt = $this->pdo->prepare("\n            INSERT INTO sige.combustivel_abastecimentos (\n                lider_id,\n                criado_por_usuario_id,\n                veiculo_descricao,\n                placa_veiculo,\n                tipo_combustivel,\n                motorista_nome,\n                local_abastecimento,\n                odometro_atual,\n                litros_abastecidos,\n                valor_total,\n                finalidade,\n                numero_nota_fiscal,\n                foto_nota_fiscal_caminho,\n                foto_nota_fiscal_nome,\n                foto_nota_fiscal_mime,\n                data_abastecimento,\n                observacoes\n            ) VALUES (\n                :lider_id,\n                :criado_por_usuario_id,\n                :veiculo_descricao,\n                :placa_veiculo,\n                :tipo_combustivel,\n                :motorista_nome,\n                :local_abastecimento,\n                :odometro_atual,\n                :litros_abastecidos,\n                :valor_total,\n                :finalidade,\n                :numero_nota_fiscal,\n                :foto_nota_fiscal_caminho,\n                :foto_nota_fiscal_nome,\n                :foto_nota_fiscal_mime,\n                :data_abastecimento,\n                :observacoes\n            )\n            RETURNING id\n        ");

        $stmt->execute([
            ':lider_id' => $dados['lider_id'],
            ':criado_por_usuario_id' => $dados['criado_por_usuario_id'] ?? null,
            ':veiculo_descricao' => $dados['veiculo_descricao'],
            ':placa_veiculo' => $dados['placa_veiculo'],
            ':tipo_combustivel' => $dados['tipo_combustivel'],
            ':motorista_nome' => $dados['motorista_nome'],
            ':local_abastecimento' => $dados['local_abastecimento'],
            ':odometro_atual' => $dados['odometro_atual'],
            ':litros_abastecidos' => $dados['litros_abastecidos'],
            ':valor_total' => $dados['valor_total'],
            ':finalidade' => $dados['finalidade'],
            ':numero_nota_fiscal' => $dados['numero_nota_fiscal'],
            ':foto_nota_fiscal_caminho' => $dados['foto_nota_fiscal_caminho'] ?? null,
            ':foto_nota_fiscal_nome' => $dados['foto_nota_fiscal_nome'] ?? null,
            ':foto_nota_fiscal_mime' => $dados['foto_nota_fiscal_mime'] ?? null,
            ':data_abastecimento' => $dados['data_abastecimento'],
            ':observacoes' => $dados['observacoes'] ?? null,
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
            'veiculo_descricao' => ':veiculo_descricao',
            'placa_veiculo' => ':placa_veiculo',
            'tipo_combustivel' => ':tipo_combustivel',
            'motorista_nome' => ':motorista_nome',
            'local_abastecimento' => ':local_abastecimento',
            'odometro_atual' => ':odometro_atual',
            'litros_abastecidos' => ':litros_abastecidos',
            'valor_total' => ':valor_total',
            'finalidade' => ':finalidade',
            'numero_nota_fiscal' => ':numero_nota_fiscal',
            'foto_nota_fiscal_caminho' => ':foto_nota_fiscal_caminho',
            'foto_nota_fiscal_nome' => ':foto_nota_fiscal_nome',
            'foto_nota_fiscal_mime' => ':foto_nota_fiscal_mime',
            'data_abastecimento' => ':data_abastecimento',
            'observacoes' => ':observacoes',
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

        $stmt = $this->pdo->prepare("UPDATE sige.combustivel_abastecimentos SET {$set} WHERE id = :id AND excluido_em IS NULL");
        $stmt->execute($params);

        return $this->buscarPorId($id);
    }

    public function remover(string $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE sige.combustivel_abastecimentos SET excluido_em = NOW() WHERE id = :id AND excluido_em IS NULL');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}