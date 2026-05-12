<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Banco;
use PDO;

class FinanceiroRepositorio
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Banco::conexao();
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function listarFornecedores(int $pagina, int $limite, string $busca = '', ?string $candidatoId = null): array
    {
        $offset = ($pagina - 1) * $limite;
        $params = [];
        $where = "WHERE COALESCE(tipo_fornecedor, '') <> 'pessoal_lider'";

        if ($busca !== '') {
            $where .= " AND (nome ILIKE :busca OR COALESCE(documento, '') ILIKE :busca OR COALESCE(tipo_fornecedor, '') ILIKE :busca OR candidato_nome ILIKE :busca)";
            $params[':busca'] = "%{$busca}%";
        }

        if (!empty($candidatoId)) {
            $where .= ' AND candidato_id = :candidato_id';
            $params[':candidato_id'] = $candidatoId;
        }

        $stmtTotal = $this->pdo->prepare("SELECT COUNT(*) FROM sige.vw_financeiro_fornecedores {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $params[':limite'] = $limite;
        $params[':offset'] = $offset;

        $stmt = $this->pdo->prepare("\n            SELECT * FROM sige.vw_financeiro_fornecedores\n            {$where}\n            ORDER BY nome ASC\n            LIMIT :limite OFFSET :offset\n        ");
        $stmt->execute($params);

        return ['dados' => $stmt->fetchAll(), 'total' => $total];
    }

    public function buscarFornecedorPorId(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sige.vw_financeiro_fornecedores WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function criarFornecedor(array $dados): array
    {
        $stmt = $this->pdo->prepare("\n            INSERT INTO sige.fornecedores_campanha (\n                candidato_id,\n                nome,\n                documento,\n                tipo_fornecedor,\n                criado_por_usuario_id\n            ) VALUES (\n                :candidato_id,\n                :nome,\n                :documento,\n                :tipo_fornecedor,\n                :criado_por_usuario_id\n            )\n            RETURNING id\n        ");

        $stmt->execute([
            ':candidato_id' => $dados['candidato_id'],
            ':nome' => $dados['nome'],
            ':documento' => $dados['documento'] ?? null,
            ':tipo_fornecedor' => $dados['tipo_fornecedor'] ?? null,
            ':criado_por_usuario_id' => $dados['criado_por_usuario_id'] ?? null,
        ]);

        return $this->buscarFornecedorPorId((string) $stmt->fetchColumn());
    }

    public function atualizarFornecedor(string $id, array $dados): ?array
    {
        $campos = [];
        $params = [':id' => $id];

        foreach (['nome', 'documento', 'tipo_fornecedor'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $campos[] = "{$campo} = :{$campo}";
                $params[":{$campo}"] = $dados[$campo];
            }
        }

        if (empty($campos)) {
            return $this->buscarFornecedorPorId($id);
        }

        $stmt = $this->pdo->prepare('UPDATE sige.fornecedores_campanha SET ' . implode(', ', $campos) . ' WHERE id = :id AND excluido_em IS NULL');
        $stmt->execute($params);

        return $this->buscarFornecedorPorId($id);
    }

    public function removerFornecedor(string $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE sige.fornecedores_campanha SET excluido_em = NOW() WHERE id = :id AND excluido_em IS NULL');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function listarReceitas(int $pagina, int $limite, string $busca = '', ?string $candidatoId = null): array
    {
        $offset = ($pagina - 1) * $limite;
        $params = [];
        $where = 'WHERE 1=1';

        if ($busca !== '') {
            $where .= " AND (tipo_recurso ILIKE :busca OR COALESCE(origem, '') ILIKE :busca OR candidato_nome ILIKE :busca)";
            $params[':busca'] = "%{$busca}%";
        }

        if (!empty($candidatoId)) {
            $where .= ' AND candidato_id = :candidato_id';
            $params[':candidato_id'] = $candidatoId;
        }

        $stmtTotal = $this->pdo->prepare("SELECT COUNT(*) FROM sige.vw_financeiro_receitas {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $params[':limite'] = $limite;
        $params[':offset'] = $offset;

        $stmt = $this->pdo->prepare("\n            SELECT * FROM sige.vw_financeiro_receitas\n            {$where}\n            ORDER BY data_recebimento DESC, criado_em DESC\n            LIMIT :limite OFFSET :offset\n        ");
        $stmt->execute($params);

        return ['dados' => $stmt->fetchAll(), 'total' => $total];
    }

    public function buscarReceitaPorId(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sige.vw_financeiro_receitas WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function buscarReceitaAtivaPorId(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sige.receitas_campanha WHERE id = :id AND excluido_em IS NULL');
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function buscarReceitaAtivaComSaldoMinimo(string $candidatoId, string $valorMinimo): ?array
    {
        $stmt = $this->pdo->prepare("\n            SELECT *
            FROM sige.receitas_campanha
            WHERE candidato_id = :candidato_id
              AND excluido_em IS NULL
              AND valor_disponivel >= :valor_minimo
            ORDER BY data_recebimento ASC, criado_em ASC
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->execute([
            ':candidato_id' => $candidatoId,
            ':valor_minimo' => $valorMinimo,
        ]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function bloquearReceitasPorIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));

        if (empty($ids)) {
            return [];
        }

        sort($ids, SORT_STRING);

        $placeholders = [];
        $params = [];

        foreach ($ids as $indice => $id) {
            $chave = ':id_' . $indice;
            $placeholders[] = $chave;
            $params[$chave] = $id;
        }

        $sql = sprintf(
            'SELECT * FROM sige.receitas_campanha WHERE id IN (%s) AND excluido_em IS NULL ORDER BY id FOR UPDATE',
            implode(', ', $placeholders)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $resultado = [];
        foreach ($stmt->fetchAll() as $linha) {
            $resultado[$linha['id']] = $linha;
        }

        return $resultado;
    }

    public function criarReceita(array $dados): array
    {
        $stmt = $this->pdo->prepare("\n            INSERT INTO sige.receitas_campanha (\n                candidato_id,\n                tipo_recurso,\n                valor_total,\n                valor_disponivel,\n                data_recebimento,\n                origem,\n                criado_por_usuario_id\n            ) VALUES (\n                :candidato_id,\n                :tipo_recurso,\n                :valor_total,\n                :valor_disponivel,\n                :data_recebimento,\n                :origem,\n                :criado_por_usuario_id\n            )\n            RETURNING id\n        ");

        $stmt->execute([
            ':candidato_id' => $dados['candidato_id'],
            ':tipo_recurso' => $dados['tipo_recurso'],
            ':valor_total' => $dados['valor_total'],
            ':valor_disponivel' => $dados['valor_disponivel'],
            ':data_recebimento' => $dados['data_recebimento'],
            ':origem' => $dados['origem'] ?? null,
            ':criado_por_usuario_id' => $dados['criado_por_usuario_id'] ?? null,
        ]);

        return $this->buscarReceitaPorId((string) $stmt->fetchColumn());
    }

    public function atualizarReceita(string $id, array $dados): ?array
    {
        $campos = [];
        $params = [':id' => $id];

        foreach (['tipo_recurso', 'valor_total', 'valor_disponivel', 'data_recebimento', 'origem'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $campos[] = "{$campo} = :{$campo}";
                $params[":{$campo}"] = $dados[$campo];
            }
        }

        if (empty($campos)) {
            return $this->buscarReceitaPorId($id);
        }

        $stmt = $this->pdo->prepare('UPDATE sige.receitas_campanha SET ' . implode(', ', $campos) . ' WHERE id = :id AND excluido_em IS NULL');
        $stmt->execute($params);

        return $this->buscarReceitaPorId($id);
    }

    public function removerReceita(string $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE sige.receitas_campanha SET excluido_em = NOW() WHERE id = :id AND excluido_em IS NULL');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function contarDespesasAtivasPorReceita(string $receitaId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM sige.despesas_campanha WHERE receita_id = :receita_id AND excluido_em IS NULL');
        $stmt->execute([':receita_id' => $receitaId]);
        return (int) $stmt->fetchColumn();
    }

    public function listarDespesas(int $pagina, int $limite, string $busca = '', ?string $candidatoId = null): array
    {
        $offset = ($pagina - 1) * $limite;
        $params = [];
        $where = 'WHERE 1=1';

        if ($busca !== '') {
            $where .= " AND (categoria ILIKE :busca OR subcategoria ILIKE :busca OR COALESCE(descricao, '') ILIKE :busca OR fornecedor_nome ILIKE :busca OR tipo_recurso ILIKE :busca OR candidato_nome ILIKE :busca)";
            $params[':busca'] = "%{$busca}%";
        }

        if (!empty($candidatoId)) {
            $where .= ' AND candidato_id = :candidato_id';
            $params[':candidato_id'] = $candidatoId;
        }

        $stmtTotal = $this->pdo->prepare("SELECT COUNT(*) FROM sige.vw_financeiro_despesas {$where}");
        $stmtTotal->execute($params);
        $total = (int) $stmtTotal->fetchColumn();

        $params[':limite'] = $limite;
        $params[':offset'] = $offset;

        $stmt = $this->pdo->prepare("\n            SELECT * FROM sige.vw_financeiro_despesas\n            {$where}\n            ORDER BY data_despesa DESC, criado_em DESC\n            LIMIT :limite OFFSET :offset\n        ");
        $stmt->execute($params);

        return ['dados' => $stmt->fetchAll(), 'total' => $total];
    }

    public function buscarDespesaPorId(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sige.vw_financeiro_despesas WHERE id = :id');
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function buscarDespesaAtivaPorId(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sige.despesas_campanha WHERE id = :id AND excluido_em IS NULL');
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function bloquearDespesaPorId(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sige.despesas_campanha WHERE id = :id AND excluido_em IS NULL FOR UPDATE');
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function criarDespesa(array $dados): array
    {
        $stmt = $this->pdo->prepare("\n            INSERT INTO sige.despesas_campanha (\n                candidato_id,\n                receita_id,\n                fornecedor_id,\n                categoria,\n                subcategoria,\n                lider_referencia_id,\n                valor,\n                data_despesa,\n                descricao,\n                classificacao_conformidade,\n                conformidade_motivo,\n                desvio_padrao_percentual,\n                criado_por_usuario_id\n            ) VALUES (\n                :candidato_id,\n                :receita_id,\n                :fornecedor_id,\n                :categoria,\n                :subcategoria,\n                :lider_referencia_id,\n                :valor,\n                :data_despesa,\n                :descricao,\n                :classificacao_conformidade,\n                :conformidade_motivo,\n                :desvio_padrao_percentual,\n                :criado_por_usuario_id\n            )\n            RETURNING id\n        ");

        $stmt->execute([
            ':candidato_id' => $dados['candidato_id'],
            ':receita_id' => $dados['receita_id'],
            ':fornecedor_id' => $dados['fornecedor_id'],
            ':categoria' => $dados['categoria'],
            ':subcategoria' => $dados['subcategoria'],
            ':lider_referencia_id' => $dados['lider_referencia_id'] ?? null,
            ':valor' => $dados['valor'],
            ':data_despesa' => $dados['data_despesa'],
            ':descricao' => $dados['descricao'] ?? null,
            ':classificacao_conformidade' => $dados['classificacao_conformidade'],
            ':conformidade_motivo' => $dados['conformidade_motivo'] ?? null,
            ':desvio_padrao_percentual' => $dados['desvio_padrao_percentual'] ?? null,
            ':criado_por_usuario_id' => $dados['criado_por_usuario_id'] ?? null,
        ]);

        return $this->buscarDespesaPorId((string) $stmt->fetchColumn());
    }

    public function atualizarDespesa(string $id, array $dados): ?array
    {
        $campos = [];
        $params = [':id' => $id];

        foreach (
            [
                'receita_id',
                'fornecedor_id',
                'categoria',
                'subcategoria',
                'lider_referencia_id',
                'valor',
                'data_despesa',
                'descricao',
                'classificacao_conformidade',
                'conformidade_motivo',
                'desvio_padrao_percentual',
            ] as $campo
        ) {
            if (array_key_exists($campo, $dados)) {
                $campos[] = "{$campo} = :{$campo}";
                $params[":{$campo}"] = $dados[$campo];
            }
        }

        if (empty($campos)) {
            return $this->buscarDespesaPorId($id);
        }

        $stmt = $this->pdo->prepare('UPDATE sige.despesas_campanha SET ' . implode(', ', $campos) . ' WHERE id = :id AND excluido_em IS NULL');
        $stmt->execute($params);

        return $this->buscarDespesaPorId($id);
    }

    public function removerDespesa(string $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE sige.despesas_campanha SET excluido_em = NOW() WHERE id = :id AND excluido_em IS NULL');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function atualizarSaldoReceita(string $receitaId, string $valorDelta): bool
    {
        $stmt = $this->pdo->prepare("\n            UPDATE sige.receitas_campanha\n            SET valor_disponivel = valor_disponivel + :valor_delta\n            WHERE id = :id\n              AND excluido_em IS NULL\n              AND (valor_disponivel + :valor_delta) >= 0\n              AND (valor_disponivel + :valor_delta) <= valor_total\n        ");

        $stmt->execute([
            ':id' => $receitaId,
            ':valor_delta' => $valorDelta,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function categoriaPermitidaParaTipo(string $tipoRecurso, string $categoria, string $subcategoria): bool
    {
        $stmt = $this->pdo->prepare("\n            SELECT COUNT(*)\n            FROM sige.categorias_permitidas_recurso\n            WHERE tipo_recurso = :tipo_recurso\n              AND categoria = :categoria\n              AND subcategoria = :subcategoria\n              AND ativo = TRUE\n        ");

        $stmt->execute([
            ':tipo_recurso' => $tipoRecurso,
            ':categoria' => $categoria,
            ':subcategoria' => $subcategoria,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function mediaDespesasCategoria(
        string $candidatoId,
        string $categoria,
        string $subcategoria,
        ?string $ignorarDespesaId = null
    ): array
    {
        $params = [
            ':candidato_id' => $candidatoId,
            ':categoria' => $categoria,
            ':subcategoria' => $subcategoria,
        ];

        $filtroIgnorar = '';
        if (!empty($ignorarDespesaId)) {
            $filtroIgnorar = ' AND id <> :ignorar_id';
            $params[':ignorar_id'] = $ignorarDespesaId;
        }

        $stmt = $this->pdo->prepare("\n            SELECT\n                COALESCE(AVG(valor), 0)::NUMERIC(14, 2) AS media_valor,\n                COUNT(*) AS total\n            FROM sige.despesas_campanha\n            WHERE candidato_id = :candidato_id\n              AND categoria = :categoria\n              AND subcategoria = :subcategoria\n              AND excluido_em IS NULL\n              {$filtroIgnorar}\n        ");
        $stmt->execute($params);

        $resultado = $stmt->fetch() ?: ['media_valor' => '0.00', 'total' => 0];

        return [
            'media_valor' => (string) ($resultado['media_valor'] ?? '0.00'),
            'total' => (int) ($resultado['total'] ?? 0),
        ];
    }

    public function parametrosFinanceiros(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM sige.financeiro_parametros WHERE id = 1');
        return $stmt->fetch() ?: [
            'limite_percentual_categoria_excessiva' => '40.00',
            'limite_percentual_saldo_critico' => '15.00',
            'fator_despesa_fora_padrao' => '2.500',
            'janela_media_despesa_dias' => 90,
        ];
    }

    public function existeLiderAtivo(string $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM sige.lideres WHERE id = :id AND excluido_em IS NULL');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function obterLiderAtivoPadraoId(): ?string
    {
        $stmt = $this->pdo->query('SELECT id FROM sige.lideres WHERE excluido_em IS NULL ORDER BY criado_em ASC LIMIT 1');
        $resultado = $stmt->fetchColumn();

        return $resultado !== false ? (string) $resultado : null;
    }

    public function buscarFornecedorAtivoPorId(string $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM sige.fornecedores_campanha WHERE id = :id AND excluido_em IS NULL');
        $stmt->execute([':id' => $id]);
        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function buscarFornecedorAtivoPorNomeETipo(string $candidatoId, string $nome, string $tipoFornecedor): ?array
    {
        $stmt = $this->pdo->prepare("\n            SELECT *
            FROM sige.fornecedores_campanha
            WHERE candidato_id = :candidato_id
              AND nome = :nome
              AND COALESCE(tipo_fornecedor, '') = :tipo_fornecedor
              AND excluido_em IS NULL
            LIMIT 1
        ");
        $stmt->execute([
            ':candidato_id' => $candidatoId,
            ':nome' => $nome,
            ':tipo_fornecedor' => $tipoFornecedor,
        ]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function buscarLiderAtivoComSalarioPorId(string $id): ?array
    {
        $stmt = $this->pdo->prepare("\n            SELECT id, nome, salario_mensal
            FROM sige.lideres
            WHERE id = :id
              AND excluido_em IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function contarDespesasPorFornecedor(string $fornecedorId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM sige.despesas_campanha WHERE fornecedor_id = :fornecedor_id AND excluido_em IS NULL');
        $stmt->execute([':fornecedor_id' => $fornecedorId]);
        return (int) $stmt->fetchColumn();
    }

    public function saldosPorTipo(?string $candidatoId = null): array
    {
        $params = [];
        $where = 'WHERE excluido_em IS NULL';

        if (!empty($candidatoId)) {
            $where .= ' AND candidato_id = :candidato_id';
            $params[':candidato_id'] = $candidatoId;
        }

        $stmt = $this->pdo->prepare("\n            SELECT\n                tipo_recurso::text AS tipo_recurso,\n                COALESCE(SUM(valor_total), 0)::NUMERIC(14, 2) AS total_recebido,\n                COALESCE(SUM(valor_total - valor_disponivel), 0)::NUMERIC(14, 2) AS total_utilizado,\n                COALESCE(SUM(valor_disponivel), 0)::NUMERIC(14, 2) AS saldo_restante\n            FROM sige.receitas_campanha\n            {$where}\n            GROUP BY tipo_recurso\n            ORDER BY tipo_recurso ASC\n        ");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function gastosPorCategoria(?string $candidatoId = null, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        $params = [];
        $where = 'WHERE excluido_em IS NULL';

        if (!empty($candidatoId)) {
            $where .= ' AND candidato_id = :candidato_id';
            $params[':candidato_id'] = $candidatoId;
        }

        if (!empty($dataInicio)) {
            $where .= ' AND data_despesa >= :data_inicio';
            $params[':data_inicio'] = $dataInicio;
        }

        if (!empty($dataFim)) {
            $where .= ' AND data_despesa <= :data_fim';
            $params[':data_fim'] = $dataFim;
        }

        $stmt = $this->pdo->prepare("\n            WITH base AS (\n                SELECT\n                    categoria,\n                    SUM(valor)::NUMERIC(14, 2) AS total_categoria\n                FROM sige.despesas_campanha\n                {$where}\n                GROUP BY categoria\n            ), total_geral AS (\n                SELECT COALESCE(SUM(total_categoria), 0)::NUMERIC(14, 2) AS total FROM base\n            )\n            SELECT\n                b.categoria,\n                b.total_categoria,\n                CASE\n                    WHEN tg.total > 0\n                        THEN ROUND(((b.total_categoria / tg.total) * 100)::NUMERIC, 2)::NUMERIC(6, 2)\n                    ELSE 0::NUMERIC(6, 2)\n                END AS percentual_uso\n            FROM base b\n            CROSS JOIN total_geral tg\n            ORDER BY b.total_categoria DESC, b.categoria ASC\n        ");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function gastosPorSubcategoria(?string $candidatoId = null, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        $params = [];
        $where = 'WHERE excluido_em IS NULL';

        if (!empty($candidatoId)) {
            $where .= ' AND candidato_id = :candidato_id';
            $params[':candidato_id'] = $candidatoId;
        }

        if (!empty($dataInicio)) {
            $where .= ' AND data_despesa >= :data_inicio';
            $params[':data_inicio'] = $dataInicio;
        }

        if (!empty($dataFim)) {
            $where .= ' AND data_despesa <= :data_fim';
            $params[':data_fim'] = $dataFim;
        }

        $stmt = $this->pdo->prepare("\n            WITH base AS (\n                SELECT\n                    categoria,\n                    subcategoria,\n                    SUM(valor)::NUMERIC(14, 2) AS total_subcategoria\n                FROM sige.despesas_campanha\n                {$where}\n                GROUP BY categoria, subcategoria\n            ), total_geral AS (\n                SELECT COALESCE(SUM(total_subcategoria), 0)::NUMERIC(14, 2) AS total FROM base\n            )\n            SELECT\n                b.categoria,\n                b.subcategoria,\n                b.total_subcategoria,\n                CASE\n                    WHEN tg.total > 0\n                        THEN ROUND(((b.total_subcategoria / tg.total) * 100)::NUMERIC, 2)::NUMERIC(6, 2)\n                    ELSE 0::NUMERIC(6, 2)\n                END AS percentual_uso\n            FROM base b\n            CROSS JOIN total_geral tg\n            ORDER BY b.total_subcategoria DESC, b.categoria ASC, b.subcategoria ASC\n        ");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function despesasConformidade(?string $candidatoId = null, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($candidatoId)) {
            $where .= ' AND candidato_id = :candidato_id';
            $params[':candidato_id'] = $candidatoId;
        }

        if (!empty($dataInicio)) {
            $where .= ' AND data_despesa >= :data_inicio';
            $params[':data_inicio'] = $dataInicio;
        }

        if (!empty($dataFim)) {
            $where .= ' AND data_despesa <= :data_fim';
            $params[':data_fim'] = $dataFim;
        }

        $stmt = $this->pdo->prepare("\n            SELECT *\n            FROM sige.vw_financeiro_despesas\n            {$where}\n              AND classificacao_conformidade IN ('suspeita', 'invalida')\n            ORDER BY\n                CASE classificacao_conformidade\n                    WHEN 'invalida' THEN 1\n                    ELSE 2\n                END,\n                data_despesa DESC\n        ");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function alertasFinanceiros(?string $candidatoId = null): array
    {
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($candidatoId)) {
            $where .= ' AND candidato_id = :candidato_id';
            $params[':candidato_id'] = $candidatoId;
        }

        $stmt = $this->pdo->prepare("\n            SELECT *\n            FROM sige.vw_financeiro_alertas\n            {$where}\n            ORDER BY\n                CASE alerta_nivel\n                    WHEN 'alto' THEN 1\n                    WHEN 'medio' THEN 2\n                    ELSE 3\n                END,\n                gerado_em DESC\n        ");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function rastreabilidade(?string $candidatoId = null, ?string $dataInicio = null, ?string $dataFim = null): array
    {
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($candidatoId)) {
            $where .= ' AND candidato_id = :candidato_id';
            $params[':candidato_id'] = $candidatoId;
        }

        if (!empty($dataInicio)) {
            $where .= ' AND data_despesa >= :data_inicio';
            $params[':data_inicio'] = $dataInicio;
        }

        if (!empty($dataFim)) {
            $where .= ' AND data_despesa <= :data_fim';
            $params[':data_fim'] = $dataFim;
        }

        $stmt = $this->pdo->prepare("\n            SELECT *\n            FROM sige.vw_financeiro_rastreabilidade\n            {$where}\n            ORDER BY data_despesa DESC, despesa_id DESC\n        ");
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
