<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Excecoes\AutorizacaoException;
use App\Core\Excecoes\NaoEncontradoException;
use App\Core\Excecoes\ValidacaoException;
use App\Repositories\FinanceiroRepositorio;
use App\Validators\FinanceiroValidador;

class FinanceiroServico
{
    private FinanceiroRepositorio $repositorio;

    public function __construct()
    {
        $this->repositorio = new FinanceiroRepositorio();
    }

    public function listarFornecedores(int $pagina, int $limite, string $busca, array $auth): array
    {
        $pagina = max(1, $pagina);
        $limite = min(100, max(1, $limite));
        $candidatoId = $this->perfilEhLider($auth) ? (string) ($auth['sub'] ?? '') : null;

        return $this->repositorio->listarFornecedores($pagina, $limite, $busca, $candidatoId);
    }

    public function buscarFornecedorPorId(string $id, array $auth): array
    {
        $fornecedor = $this->repositorio->buscarFornecedorPorId($id);

        if (!$fornecedor) {
            throw new NaoEncontradoException('Fornecedor de campanha');
        }

        $this->garantirAcessoCandidato((string) $fornecedor['candidato_id'], $auth);

        return $fornecedor;
    }

    public function cadastrarFornecedor(array $dados, array $auth): array
    {
        if (!$this->podeGerenciarFinanceiro($auth)) {
            throw new AutorizacaoException('Apenas lideres, gestores e administradores podem cadastrar fornecedores.');
        }

        $dados['candidato_id'] = $this->resolverCandidatoId($dados, $auth);

        $erros = FinanceiroValidador::validarFornecedorCadastro($dados);
        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        if (!$this->repositorio->existeLiderAtivo((string) $dados['candidato_id'])) {
            throw new NaoEncontradoException('Candidato');
        }

        return $this->repositorio->criarFornecedor([
            'candidato_id' => (string) $dados['candidato_id'],
            'nome' => $this->normalizarTextoObrigatorio((string) $dados['nome']),
            'documento' => $this->normalizarTexto($dados['documento'] ?? null),
            'tipo_fornecedor' => $this->normalizarTexto($dados['tipo_fornecedor'] ?? null),
            'criado_por_usuario_id' => $this->perfilEhLider($auth) ? null : ($auth['sub'] ?? null),
        ]);
    }

    public function atualizarFornecedor(string $id, array $dados, array $auth): array
    {
        $fornecedor = $this->buscarFornecedorPorId($id, $auth);

        $erros = FinanceiroValidador::validarFornecedorAtualizacao($dados);
        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        $payload = [];

        if (array_key_exists('nome', $dados)) {
            $payload['nome'] = $this->normalizarTextoObrigatorio((string) $dados['nome']);
        }

        if (array_key_exists('documento', $dados)) {
            $payload['documento'] = $this->normalizarTexto($dados['documento']);
        }

        if (array_key_exists('tipo_fornecedor', $dados)) {
            $payload['tipo_fornecedor'] = $this->normalizarTexto($dados['tipo_fornecedor']);
        }

        $resultado = $this->repositorio->atualizarFornecedor($id, $payload);

        if (!$resultado) {
            throw new NaoEncontradoException('Fornecedor de campanha');
        }

        if ((string) $fornecedor['candidato_id'] !== (string) $resultado['candidato_id']) {
            throw new ValidacaoException(['Atualizacao inconsistente de fornecedor.']);
        }

        return $resultado;
    }

    public function removerFornecedor(string $id, array $auth): void
    {
        $this->buscarFornecedorPorId($id, $auth);

        if ($this->repositorio->contarDespesasPorFornecedor($id) > 0) {
            throw new ValidacaoException(['Nao e permitido remover fornecedor com despesas ativas vinculadas.']);
        }

        if (!$this->repositorio->removerFornecedor($id)) {
            throw new NaoEncontradoException('Fornecedor de campanha');
        }
    }

    public function listarReceitas(int $pagina, int $limite, string $busca, array $auth): array
    {
        $pagina = max(1, $pagina);
        $limite = min(100, max(1, $limite));
        $candidatoId = $this->perfilEhLider($auth) ? (string) ($auth['sub'] ?? '') : null;

        return $this->repositorio->listarReceitas($pagina, $limite, $busca, $candidatoId);
    }

    public function buscarReceitaPorId(string $id, array $auth): array
    {
        $receita = $this->repositorio->buscarReceitaPorId($id);

        if (!$receita) {
            throw new NaoEncontradoException('Receita de campanha');
        }

        $this->garantirAcessoCandidato((string) $receita['candidato_id'], $auth);

        return $receita;
    }

    public function cadastrarReceita(array $dados, array $auth): array
    {
        if (!$this->podeGerenciarFinanceiro($auth)) {
            throw new AutorizacaoException('Apenas lideres, gestores e administradores podem cadastrar receitas.');
        }

        $dados['candidato_id'] = $this->resolverCandidatoId($dados, $auth);

        $erros = FinanceiroValidador::validarReceitaCadastro($dados);
        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        if (!$this->repositorio->existeLiderAtivo((string) $dados['candidato_id'])) {
            throw new NaoEncontradoException('Candidato');
        }

        $valorTotal = $this->normalizarValor($dados['valor_total']);

        return $this->repositorio->criarReceita([
            'candidato_id' => (string) $dados['candidato_id'],
            'tipo_recurso' => $this->normalizarTipoRecurso((string) $dados['tipo_recurso']),
            'valor_total' => $valorTotal,
            'valor_disponivel' => $valorTotal,
            'data_recebimento' => $this->normalizarData((string) $dados['data_recebimento']),
            'origem' => $this->normalizarTexto($dados['origem'] ?? null),
            'criado_por_usuario_id' => $this->perfilEhLider($auth) ? null : ($auth['sub'] ?? null),
        ]);
    }

    public function atualizarReceita(string $id, array $dados, array $auth): array
    {
        $receitaAtual = $this->buscarReceitaPorId($id, $auth);

        $erros = FinanceiroValidador::validarReceitaAtualizacao($dados);
        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        $payload = [];
        $tipoAtual = (string) $receitaAtual['tipo_recurso'];

        if (array_key_exists('tipo_recurso', $dados)) {
            $payload['tipo_recurso'] = $this->normalizarTipoRecurso((string) $dados['tipo_recurso']);
        }

        if (array_key_exists('data_recebimento', $dados)) {
            $payload['data_recebimento'] = $this->normalizarData((string) $dados['data_recebimento']);
        }

        if (array_key_exists('origem', $dados)) {
            $payload['origem'] = $this->normalizarTexto($dados['origem']);
        }

        $pdo = $this->repositorio->getPdo();

        try {
            $pdo->beginTransaction();

            $receitasBloqueadas = $this->repositorio->bloquearReceitasPorIds([$id]);
            $receita = $receitasBloqueadas[$id] ?? null;

            if (!$receita) {
                throw new NaoEncontradoException('Receita de campanha');
            }

            $consumido = (float) $receita['valor_total'] - (float) $receita['valor_disponivel'];

            if (array_key_exists('valor_total', $dados)) {
                $novoTotal = (float) $this->normalizarValor($dados['valor_total']);
                if ($novoTotal < $consumido) {
                    throw new ValidacaoException(['O novo valor total nao pode ser menor que o valor ja utilizado nesta receita.']);
                }

                $payload['valor_total'] = number_format($novoTotal, 2, '.', '');
                $payload['valor_disponivel'] = number_format($novoTotal - $consumido, 2, '.', '');
            }

            if (isset($payload['tipo_recurso']) && $payload['tipo_recurso'] !== $tipoAtual) {
                if ($this->repositorio->contarDespesasAtivasPorReceita($id) > 0) {
                    throw new ValidacaoException([
                        'Nao e permitido alterar o tipo de recurso de uma receita que ja possui despesas vinculadas.',
                    ]);
                }
            }

            $resultado = $this->repositorio->atualizarReceita($id, $payload);

            if (!$resultado) {
                throw new NaoEncontradoException('Receita de campanha');
            }

            $pdo->commit();
            return $resultado;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function removerReceita(string $id, array $auth): void
    {
        $this->buscarReceitaPorId($id, $auth);

        if ($this->repositorio->contarDespesasAtivasPorReceita($id) > 0) {
            throw new ValidacaoException(['Nao e permitido remover receita com despesas ativas vinculadas.']);
        }

        if (!$this->repositorio->removerReceita($id)) {
            throw new NaoEncontradoException('Receita de campanha');
        }
    }

    public function listarDespesas(int $pagina, int $limite, string $busca, array $auth): array
    {
        $pagina = max(1, $pagina);
        $limite = min(100, max(1, $limite));
        $candidatoId = $this->perfilEhLider($auth) ? (string) ($auth['sub'] ?? '') : null;

        return $this->repositorio->listarDespesas($pagina, $limite, $busca, $candidatoId);
    }

    public function buscarDespesaPorId(string $id, array $auth): array
    {
        $despesa = $this->repositorio->buscarDespesaPorId($id);

        if (!$despesa) {
            throw new NaoEncontradoException('Despesa de campanha');
        }

        $this->garantirAcessoCandidato((string) $despesa['candidato_id'], $auth);

        return $despesa;
    }

    public function cadastrarDespesa(array $dados, array $auth): array
    {
        if (!$this->podeGerenciarFinanceiro($auth)) {
            throw new AutorizacaoException('Apenas lideres, gestores e administradores podem cadastrar despesas.');
        }

        $dados['candidato_id'] = $this->resolverCandidatoId($dados, $auth);

        $erros = FinanceiroValidador::validarDespesaCadastro($dados);
        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        $payloadBase = [
            'candidato_id' => (string) $dados['candidato_id'],
            'receita_id' => (string) $dados['receita_id'],
            'fornecedor_id' => (string) $dados['fornecedor_id'],
            'categoria' => $this->normalizarCategoria((string) $dados['categoria']),
            'subcategoria' => $this->normalizarSubcategoria((string) $dados['subcategoria']),
            'lider_referencia_id' => isset($dados['lider_referencia_id']) && $dados['lider_referencia_id'] !== ''
                ? (string) $dados['lider_referencia_id']
                : null,
            'valor' => $this->normalizarValor($dados['valor']),
            'data_despesa' => $this->normalizarData((string) $dados['data']),
            'descricao' => $this->normalizarTextoObrigatorio((string) $dados['descricao']),
            'criado_por_usuario_id' => $this->perfilEhLider($auth) ? null : ($auth['sub'] ?? null),
        ];

        $pdo = $this->repositorio->getPdo();

        try {
            $pdo->beginTransaction();

            $fornecedor = $this->repositorio->buscarFornecedorAtivoPorId($payloadBase['fornecedor_id']);
            if (!$fornecedor) {
                throw new NaoEncontradoException('Fornecedor de campanha');
            }

            if ((string) $fornecedor['candidato_id'] !== $payloadBase['candidato_id']) {
                throw new ValidacaoException(['Fornecedor nao pertence ao mesmo candidato da despesa.']);
            }

            $receitas = $this->repositorio->bloquearReceitasPorIds([$payloadBase['receita_id']]);
            $receita = $receitas[$payloadBase['receita_id']] ?? null;

            if (!$receita) {
                throw new NaoEncontradoException('Receita de campanha');
            }

            if ((string) $receita['candidato_id'] !== $payloadBase['candidato_id']) {
                throw new ValidacaoException(['Receita informada nao pertence ao candidato da despesa.']);
            }

            $valorDespesa = (float) $payloadBase['valor'];
            $saldoDisponivel = (float) $receita['valor_disponivel'];

            if ($saldoDisponivel < $valorDespesa) {
                throw new ValidacaoException(['Saldo insuficiente na receita de origem para lancar esta despesa.']);
            }

            $conformidade = $this->classificarConformidade(
                (string) $receita['tipo_recurso'],
                $payloadBase['categoria'],
                $payloadBase['subcategoria'],
                $payloadBase['candidato_id'],
                $valorDespesa,
                null
            );

            $resultado = $this->repositorio->criarDespesa(array_merge($payloadBase, $conformidade));

            $debitoOk = $this->repositorio->atualizarSaldoReceita($payloadBase['receita_id'], '-' . $payloadBase['valor']);

            if (!$debitoOk) {
                throw new ValidacaoException(['Nao foi possivel debitar a receita. Verifique saldo disponivel.']);
            }

            $pdo->commit();
            return $resultado;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function atualizarDespesa(string $id, array $dados, array $auth): array
    {
        $despesaAtual = $this->buscarDespesaPorId($id, $auth);

        $erros = FinanceiroValidador::validarDespesaAtualizacao($dados);
        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        $payload = [];

        if (array_key_exists('receita_id', $dados)) {
            $payload['receita_id'] = (string) $dados['receita_id'];
        }

        if (array_key_exists('fornecedor_id', $dados)) {
            $payload['fornecedor_id'] = (string) $dados['fornecedor_id'];
        }

        if (array_key_exists('categoria', $dados)) {
            $payload['categoria'] = $this->normalizarCategoria((string) $dados['categoria']);
        }

        if (array_key_exists('subcategoria', $dados)) {
            $payload['subcategoria'] = $this->normalizarSubcategoria((string) $dados['subcategoria']);
        }

        if (array_key_exists('lider_referencia_id', $dados)) {
            $payload['lider_referencia_id'] = ($dados['lider_referencia_id'] === null || $dados['lider_referencia_id'] === '')
                ? null
                : (string) $dados['lider_referencia_id'];
        }

        if (array_key_exists('valor', $dados)) {
            $payload['valor'] = $this->normalizarValor($dados['valor']);
        }

        if (array_key_exists('data', $dados)) {
            $payload['data_despesa'] = $this->normalizarData((string) $dados['data']);
        }

        if (array_key_exists('descricao', $dados)) {
            $payload['descricao'] = $this->normalizarTextoObrigatorio((string) $dados['descricao']);
        }

        $pdo = $this->repositorio->getPdo();

        try {
            $pdo->beginTransaction();

            $despesaBloqueada = $this->repositorio->bloquearDespesaPorId($id);
            if (!$despesaBloqueada) {
                throw new NaoEncontradoException('Despesa de campanha');
            }

            $receitaIdOriginal = (string) $despesaBloqueada['receita_id'];
            $receitaIdNovo = (string) ($payload['receita_id'] ?? $receitaIdOriginal);

            $receitas = $this->repositorio->bloquearReceitasPorIds([$receitaIdOriginal, $receitaIdNovo]);
            $receitaOriginal = $receitas[$receitaIdOriginal] ?? null;
            $receitaNova = $receitas[$receitaIdNovo] ?? null;

            if (!$receitaOriginal || !$receitaNova) {
                throw new NaoEncontradoException('Receita de campanha');
            }

            $candidatoId = (string) $despesaBloqueada['candidato_id'];

            if ((string) $receitaNova['candidato_id'] !== $candidatoId) {
                throw new ValidacaoException(['A nova receita nao pertence ao mesmo candidato da despesa.']);
            }

            $fornecedorId = (string) ($payload['fornecedor_id'] ?? $despesaBloqueada['fornecedor_id']);
            $fornecedor = $this->repositorio->buscarFornecedorAtivoPorId($fornecedorId);

            if (!$fornecedor) {
                throw new NaoEncontradoException('Fornecedor de campanha');
            }

            if ((string) $fornecedor['candidato_id'] !== $candidatoId) {
                throw new ValidacaoException(['Fornecedor nao pertence ao mesmo candidato da despesa.']);
            }

            $valorOriginal = (float) $despesaBloqueada['valor'];
            $valorNovo = (float) ($payload['valor'] ?? $despesaBloqueada['valor']);
            $categoriaNova = (string) ($payload['categoria'] ?? $despesaBloqueada['categoria']);
            $subcategoriaNova = (string) ($payload['subcategoria'] ?? $despesaBloqueada['subcategoria']);

            $creditoOriginalOk = $this->repositorio->atualizarSaldoReceita($receitaIdOriginal, number_format($valorOriginal, 2, '.', ''));
            if (!$creditoOriginalOk) {
                throw new ValidacaoException(['Nao foi possivel estornar o saldo da receita original.']);
            }

            $debitoNovoOk = $this->repositorio->atualizarSaldoReceita($receitaIdNovo, '-' . number_format($valorNovo, 2, '.', ''));
            if (!$debitoNovoOk) {
                throw new ValidacaoException(['Saldo insuficiente na receita selecionada para atualizar a despesa.']);
            }

            $conformidade = $this->classificarConformidade(
                (string) $receitaNova['tipo_recurso'],
                $categoriaNova,
                $subcategoriaNova,
                $candidatoId,
                $valorNovo,
                $id
            );

            $payload['fornecedor_id'] = $fornecedorId;

            $resultado = $this->repositorio->atualizarDespesa($id, array_merge($payload, $conformidade));

            if (!$resultado) {
                throw new NaoEncontradoException('Despesa de campanha');
            }

            $pdo->commit();
            return $resultado;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function lancarDespesaPessoalLider(array $dados, array $auth): array
    {
        if (!$this->podeGerenciarFinanceiro($auth)) {
            throw new AutorizacaoException('Apenas lideres, gestores e administradores podem lancar despesas salariais.');
        }

        $candidatoId = $this->resolverCandidatoId($dados, $auth);
        $liderId = trim((string) ($dados['lider_id'] ?? ''));
        $receitaId = trim((string) ($dados['receita_id'] ?? ''));
        $data = trim((string) ($dados['data'] ?? ''));

        if ($liderId === '') {
            throw new ValidacaoException(['Informe lider_id para lancar despesa salarial.']);
        }

        $dataNormalizada = $data !== '' ? $this->normalizarData($data) : date('Y-m-d');
        $lider = $this->repositorio->buscarLiderAtivoComSalarioPorId($liderId);

        if (!$lider) {
            throw new NaoEncontradoException('Lider para despesa salarial');
        }

        $salario = (float) ($lider['salario_mensal'] ?? 0);
        if ($salario <= 0) {
            throw new ValidacaoException(['O lider informado nao possui salario mensal definido.']);
        }

        $fornecedorNome = sprintf('Lider - %s', trim((string) $lider['nome']));
        $fornecedorTipo = 'pessoal_lider';

        $pdo = $this->repositorio->getPdo();

        try {
            $pdo->beginTransaction();

            $fornecedor = $this->repositorio->buscarFornecedorAtivoPorNomeETipo($candidatoId, $fornecedorNome, $fornecedorTipo);

            if (!$fornecedor) {
                $fornecedor = $this->repositorio->criarFornecedor([
                    'candidato_id' => $candidatoId,
                    'nome' => $fornecedorNome,
                    'documento' => null,
                    'tipo_fornecedor' => $fornecedorTipo,
                    'criado_por_usuario_id' => $this->perfilEhLider($auth) ? null : ($auth['sub'] ?? null),
                ]);
            }

            $receita = null;

            if ($receitaId !== '') {
                $receitas = $this->repositorio->bloquearReceitasPorIds([$receitaId]);
                $receita = $receitas[$receitaId] ?? null;
            } else {
                $receita = $this->repositorio->buscarReceitaAtivaComSaldoMinimo($candidatoId, $this->normalizarValor($salario));
                if ($receita !== null) {
                    $receitaId = (string) $receita['id'];
                }
            }

            if (!$receita) {
                throw new ValidacaoException(['Nao existe receita com saldo suficiente para lancar automaticamente o salario deste lider.']);
            }

            if ((string) $receita['candidato_id'] !== $candidatoId) {
                throw new ValidacaoException(['Receita informada nao pertence ao candidato da campanha.']);
            }

            if ((float) $receita['valor_disponivel'] < $salario) {
                throw new ValidacaoException(['Saldo insuficiente na receita de origem para lancar o salario do lider.']);
            }

            $competencia = substr($dataNormalizada, 0, 7);
            $descricao = $this->normalizarTexto($dados['descricao'] ?? null)
                ?? sprintf('Salario do lider %s - competencia %s', (string) $lider['nome'], $competencia);

            $categoria = 'equipe_campanha';
            $subcategoria = 'salario_lider';

            $conformidade = $this->classificarConformidade(
                (string) $receita['tipo_recurso'],
                $categoria,
                $subcategoria,
                $candidatoId,
                $salario,
                null
            );

            $despesa = $this->repositorio->criarDespesa([
                'candidato_id' => $candidatoId,
                'receita_id' => $receitaId,
                'fornecedor_id' => (string) $fornecedor['id'],
                'categoria' => $categoria,
                'subcategoria' => $subcategoria,
                'lider_referencia_id' => $liderId,
                'valor' => $this->normalizarValor($salario),
                'data_despesa' => $dataNormalizada,
                'descricao' => $descricao,
                'classificacao_conformidade' => $conformidade['classificacao_conformidade'],
                'conformidade_motivo' => $conformidade['conformidade_motivo'],
                'desvio_padrao_percentual' => $conformidade['desvio_padrao_percentual'],
                'criado_por_usuario_id' => $this->perfilEhLider($auth) ? null : ($auth['sub'] ?? null),
            ]);

            $debitoOk = $this->repositorio->atualizarSaldoReceita($receitaId, '-' . $this->normalizarValor($salario));

            if (!$debitoOk) {
                throw new ValidacaoException(['Nao foi possivel debitar a receita ao lancar despesa salarial.']);
            }

            $pdo->commit();
            return $despesa;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function removerDespesa(string $id, array $auth): void
    {
        $this->buscarDespesaPorId($id, $auth);

        $pdo = $this->repositorio->getPdo();

        try {
            $pdo->beginTransaction();

            $despesa = $this->repositorio->bloquearDespesaPorId($id);
            if (!$despesa) {
                throw new NaoEncontradoException('Despesa de campanha');
            }

            $receitas = $this->repositorio->bloquearReceitasPorIds([(string) $despesa['receita_id']]);
            $receita = $receitas[(string) $despesa['receita_id']] ?? null;

            if (!$receita) {
                throw new NaoEncontradoException('Receita de campanha');
            }

            if (!$this->repositorio->removerDespesa($id)) {
                throw new NaoEncontradoException('Despesa de campanha');
            }

            $estornoOk = $this->repositorio->atualizarSaldoReceita(
                (string) $despesa['receita_id'],
                $this->normalizarValor($despesa['valor'])
            );

            if (!$estornoOk) {
                throw new ValidacaoException(['Nao foi possivel estornar o saldo da receita ao remover a despesa.']);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function saldos(array $auth, ?string $candidatoIdQuery = null): array
    {
        $candidatoId = $this->resolverCandidatoConsulta($auth, $candidatoIdQuery);

        $porTipo = $this->repositorio->saldosPorTipo($candidatoId);
        $porCategoria = $this->repositorio->gastosPorCategoria($candidatoId);

        $totais = [
            'total_recebido' => '0.00',
            'total_utilizado' => '0.00',
            'total_saldo' => '0.00',
        ];

        foreach ($porTipo as $item) {
            $totais['total_recebido'] = $this->somarDecimal($totais['total_recebido'], (string) $item['total_recebido']);
            $totais['total_utilizado'] = $this->somarDecimal($totais['total_utilizado'], (string) $item['total_utilizado']);
            $totais['total_saldo'] = $this->somarDecimal($totais['total_saldo'], (string) $item['saldo_restante']);
        }

        return [
            'totais' => $totais,
            'saldo_por_tipo_recurso' => $porTipo,
            'uso_por_categoria' => $porCategoria,
        ];
    }

    public function relatorioInteligente(array $auth, ?string $candidatoIdQuery, ?string $dataInicio, ?string $dataFim): array
    {
        $candidatoId = $this->resolverCandidatoConsulta($auth, $candidatoIdQuery);

        $inicio = $this->normalizarDataOpcional($dataInicio);
        $fim = $this->normalizarDataOpcional($dataFim);

        if ($inicio !== null && $fim !== null && $inicio > $fim) {
            throw new ValidacaoException(['A data inicial nao pode ser maior que a data final.']);
        }

        return [
            'receitas_por_tipo' => $this->repositorio->saldosPorTipo($candidatoId),
            'gastos_por_categoria' => $this->repositorio->gastosPorCategoria($candidatoId, $inicio, $fim),
            'gastos_por_subcategoria' => $this->repositorio->gastosPorSubcategoria($candidatoId, $inicio, $fim),
            'despesas_nao_conformes' => $this->repositorio->despesasConformidade($candidatoId, $inicio, $fim),
            'alertas' => $this->repositorio->alertasFinanceiros($candidatoId),
        ];
    }

    public function alertas(array $auth, ?string $candidatoIdQuery): array
    {
        $candidatoId = $this->resolverCandidatoConsulta($auth, $candidatoIdQuery);
        return $this->repositorio->alertasFinanceiros($candidatoId);
    }

    public function auditoria(array $auth, ?string $candidatoIdQuery, ?string $dataInicio, ?string $dataFim): array
    {
        $candidatoId = $this->resolverCandidatoConsulta($auth, $candidatoIdQuery);

        $inicio = $this->normalizarDataOpcional($dataInicio);
        $fim = $this->normalizarDataOpcional($dataFim);

        if ($inicio !== null && $fim !== null && $inicio > $fim) {
            throw new ValidacaoException(['A data inicial nao pode ser maior que a data final.']);
        }

        return $this->repositorio->rastreabilidade($candidatoId, $inicio, $fim);
    }

    public function prepararPrestacaoContas(array $auth, ?string $candidatoIdQuery, ?string $dataInicio, ?string $dataFim): array
    {
        $candidatoId = $this->resolverCandidatoConsulta($auth, $candidatoIdQuery);

        $inicio = $this->normalizarDataOpcional($dataInicio);
        $fim = $this->normalizarDataOpcional($dataFim);

        if ($inicio !== null && $fim !== null && $inicio > $fim) {
            throw new ValidacaoException(['A data inicial nao pode ser maior que a data final.']);
        }

        $saldos = $this->repositorio->saldosPorTipo($candidatoId);
        $gastosCategoria = $this->repositorio->gastosPorCategoria($candidatoId, $inicio, $fim);
        $gastosSubcategoria = $this->repositorio->gastosPorSubcategoria($candidatoId, $inicio, $fim);
        $rastreabilidade = $this->repositorio->rastreabilidade($candidatoId, $inicio, $fim);
        $naoConformes = $this->repositorio->despesasConformidade($candidatoId, $inicio, $fim);

        return [
            'metadata' => [
                'formato' => 'prestacao_contas_tse_preparacao',
                'gerado_em' => date('Y-m-d H:i:s'),
                'periodo_inicio' => $inicio,
                'periodo_fim' => $fim,
                'candidato_id' => $candidatoId,
                'total_registros_rastreabilidade' => count($rastreabilidade),
            ],
            'resumo_saldo_por_tipo' => $saldos,
            'resumo_gasto_por_categoria' => $gastosCategoria,
            'resumo_gasto_por_subcategoria' => $gastosSubcategoria,
            'despesas_nao_conformes' => $naoConformes,
            'rastreabilidade_receita_despesa' => $rastreabilidade,
        ];
    }

    private function classificarConformidade(
        string $tipoRecurso,
        string $categoria,
        string $subcategoria,
        string $candidatoId,
        float $valor,
        ?string $ignorarDespesaId
    ): array {
        $categoriaPermitida = $this->repositorio->categoriaPermitidaParaTipo($tipoRecurso, $categoria, $subcategoria);

        if (!$categoriaPermitida) {
            return [
                'classificacao_conformidade' => 'invalida',
                'conformidade_motivo' => sprintf(
                    'Categoria/subcategoria %s/%s nao permitida para o tipo de recurso %s.',
                    $categoria,
                    $subcategoria,
                    $tipoRecurso
                ),
                'desvio_padrao_percentual' => null,
            ];
        }

        $parametros = $this->repositorio->parametrosFinanceiros();
        $fatorForaPadrao = (float) ($parametros['fator_despesa_fora_padrao'] ?? 2.5);
        $estatistica = $this->repositorio->mediaDespesasCategoria($candidatoId, $categoria, $subcategoria, $ignorarDespesaId);

        $media = (float) $estatistica['media_valor'];
        $total = (int) $estatistica['total'];

        if ($total >= 3 && $media > 0 && $valor >= ($media * $fatorForaPadrao)) {
            $desvio = (($valor / $media) - 1) * 100;

            return [
                'classificacao_conformidade' => 'suspeita',
                'conformidade_motivo' => 'Despesa acima do padrao medio historico da categoria.',
                'desvio_padrao_percentual' => number_format($desvio, 2, '.', ''),
            ];
        }

        return [
            'classificacao_conformidade' => 'valida',
            'conformidade_motivo' => null,
            'desvio_padrao_percentual' => null,
        ];
    }

    private function resolverCandidatoConsulta(array $auth, ?string $candidatoIdQuery): ?string
    {
        if ($this->perfilEhLider($auth)) {
            return (string) ($auth['sub'] ?? '');
        }

        if ($candidatoIdQuery === null || trim($candidatoIdQuery) === '') {
            return null;
        }

        if (!$this->repositorio->existeLiderAtivo($candidatoIdQuery)) {
            throw new NaoEncontradoException('Candidato');
        }

        return $candidatoIdQuery;
    }

    private function resolverCandidatoId(array $dados, array $auth): string
    {
        if ($this->perfilEhLider($auth)) {
            return (string) ($auth['sub'] ?? '');
        }

        $candidatoInformado = trim((string) ($dados['candidato_id'] ?? ''));

        if ($candidatoInformado !== '' && $this->repositorio->existeLiderAtivo($candidatoInformado)) {
            return $candidatoInformado;
        }

        $candidatoPadrao = $this->repositorio->obterLiderAtivoPadraoId();
        if ($candidatoPadrao !== null) {
            return $candidatoPadrao;
        }

        throw new ValidacaoException(['Nenhum candidato ativo foi encontrado para associar o lancamento financeiro.']);
    }

    private function garantirAcessoCandidato(string $candidatoId, array $auth): void
    {
        if ($this->perfilEhLider($auth) && $candidatoId !== (string) ($auth['sub'] ?? '')) {
            throw new AutorizacaoException('Voce nao pode acessar dados financeiros de outro candidato.');
        }
    }

    private function perfilEhLider(array $auth): bool
    {
        return ($auth['perfil'] ?? null) === 'lider' && ($auth['tipo'] ?? null) === 'lider';
    }

    private function podeGerenciarFinanceiro(array $auth): bool
    {
        return $this->perfilEhLider($auth) || in_array($auth['perfil'] ?? '', ['admin', 'gestor'], true);
    }

    private function normalizarTipoRecurso(string $valor): string
    {
        return mb_strtolower(trim($valor));
    }

    private function normalizarCategoria(string $valor): string
    {
        return mb_strtolower(trim($valor));
    }

    private function normalizarSubcategoria(string $valor): string
    {
        return mb_strtolower(trim($valor));
    }

    private function normalizarValor(mixed $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }

    private function normalizarData(string $valor): string
    {
        return (new \DateTimeImmutable($valor))->format('Y-m-d');
    }

    private function normalizarDataOpcional(?string $valor): ?string
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        return $this->normalizarData($valor);
    }

    private function normalizarTexto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim($valor);
        return $texto === '' ? null : $texto;
    }

    private function normalizarTextoObrigatorio(string $valor): string
    {
        return trim($valor);
    }

    private function somarDecimal(string $a, string $b): string
    {
        return number_format(((float) $a) + ((float) $b), 2, '.', '');
    }
}
