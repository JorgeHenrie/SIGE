<?php

declare(strict_types=1);

namespace App\Validators;

final class FinanceiroValidador
{
    public static function validarFornecedorCadastro(array $dados): array
    {
        $erros = [];

        if (empty($dados['candidato_id'])) {
            $erros[] = 'O candidato responsavel pelo fornecedor e obrigatorio.';
        }

        self::validarTextoObrigatorio($dados, 'nome', 'O nome do fornecedor e obrigatorio.', 160, $erros, true);

        if (isset($dados['documento']) && mb_strlen(trim((string) $dados['documento'])) > 20) {
            $erros[] = 'O documento do fornecedor deve ter no maximo 20 caracteres.';
        }

        if (isset($dados['tipo_fornecedor']) && mb_strlen(trim((string) $dados['tipo_fornecedor'])) > 40) {
            $erros[] = 'O tipo de fornecedor deve ter no maximo 40 caracteres.';
        }

        return $erros;
    }

    public static function validarFornecedorAtualizacao(array $dados): array
    {
        $erros = [];

        self::validarTextoObrigatorio($dados, 'nome', 'O nome do fornecedor e obrigatorio.', 160, $erros, false);

        if (isset($dados['documento']) && mb_strlen(trim((string) $dados['documento'])) > 20) {
            $erros[] = 'O documento do fornecedor deve ter no maximo 20 caracteres.';
        }

        if (isset($dados['tipo_fornecedor']) && mb_strlen(trim((string) $dados['tipo_fornecedor'])) > 40) {
            $erros[] = 'O tipo de fornecedor deve ter no maximo 40 caracteres.';
        }

        return $erros;
    }

    public static function validarReceitaCadastro(array $dados): array
    {
        $erros = [];

        if (empty($dados['candidato_id'])) {
            $erros[] = 'O candidato responsavel pela receita e obrigatorio.';
        }

        if (!self::tipoRecursoValido((string) ($dados['tipo_recurso'] ?? ''))) {
            $erros[] = 'Informe um tipo de recurso valido.';
        }

        if (!array_key_exists('valor_total', $dados) || !is_numeric($dados['valor_total']) || (float) $dados['valor_total'] <= 0) {
            $erros[] = 'O valor total da receita deve ser numerico e maior que zero.';
        }

        if (empty($dados['data_recebimento']) || !self::dataValida((string) $dados['data_recebimento'])) {
            $erros[] = 'A data de recebimento e obrigatoria e deve ser valida.';
        }

        if (isset($dados['origem']) && mb_strlen(trim((string) $dados['origem'])) > 180) {
            $erros[] = 'A origem da receita deve ter no maximo 180 caracteres.';
        }

        return $erros;
    }

    public static function validarReceitaAtualizacao(array $dados): array
    {
        $erros = [];

        if (array_key_exists('tipo_recurso', $dados) && !self::tipoRecursoValido((string) $dados['tipo_recurso'])) {
            $erros[] = 'Informe um tipo de recurso valido.';
        }

        if (array_key_exists('valor_total', $dados) && (!is_numeric($dados['valor_total']) || (float) $dados['valor_total'] <= 0)) {
            $erros[] = 'O valor total da receita deve ser numerico e maior que zero.';
        }

        if (array_key_exists('data_recebimento', $dados) && !self::dataValida((string) $dados['data_recebimento'])) {
            $erros[] = 'A data de recebimento deve ser valida.';
        }

        if (isset($dados['origem']) && mb_strlen(trim((string) $dados['origem'])) > 180) {
            $erros[] = 'A origem da receita deve ter no maximo 180 caracteres.';
        }

        return $erros;
    }

    public static function validarDespesaCadastro(array $dados): array
    {
        $erros = [];

        if (empty($dados['candidato_id'])) {
            $erros[] = 'O candidato responsavel pela despesa e obrigatorio.';
        }

        if (empty($dados['receita_id'])) {
            $erros[] = 'A origem do recurso (receita_id) e obrigatoria.';
        }

        if (empty($dados['fornecedor_id'])) {
            $erros[] = 'O fornecedor e obrigatorio.';
        }

        self::validarTextoObrigatorio($dados, 'categoria', 'A categoria da despesa e obrigatoria.', 60, $erros, true);
        self::validarTextoObrigatorio($dados, 'subcategoria', 'A subcategoria da despesa e obrigatoria.', 80, $erros, true);

        if (!array_key_exists('valor', $dados) || !is_numeric($dados['valor']) || (float) $dados['valor'] <= 0) {
            $erros[] = 'O valor da despesa deve ser numerico e maior que zero.';
        }

        if (empty($dados['data']) || !self::dataValida((string) $dados['data'])) {
            $erros[] = 'A data da despesa e obrigatoria e deve ser valida.';
        }

        self::validarTextoObrigatorio($dados, 'descricao', 'A descricao da despesa e obrigatoria.', 2000, $erros, true);

        return $erros;
    }

    public static function validarDespesaAtualizacao(array $dados): array
    {
        $erros = [];

        if (array_key_exists('categoria', $dados)) {
            self::validarTextoObrigatorio($dados, 'categoria', 'A categoria da despesa e obrigatoria.', 60, $erros, false);
        }

        if (array_key_exists('subcategoria', $dados)) {
            self::validarTextoObrigatorio($dados, 'subcategoria', 'A subcategoria da despesa e obrigatoria.', 80, $erros, false);
        }

        if (array_key_exists('valor', $dados) && (!is_numeric($dados['valor']) || (float) $dados['valor'] <= 0)) {
            $erros[] = 'O valor da despesa deve ser numerico e maior que zero.';
        }

        if (array_key_exists('data', $dados) && !self::dataValida((string) $dados['data'])) {
            $erros[] = 'A data da despesa deve ser valida.';
        }

        if (array_key_exists('descricao', $dados)) {
            self::validarTextoObrigatorio($dados, 'descricao', 'A descricao da despesa e obrigatoria.', 2000, $erros, false);
        }

        return $erros;
    }

    private static function tipoRecursoValido(string $tipo): bool
    {
        return in_array(mb_strtolower(trim($tipo)), ['fundo_partidario', 'fundo_eleitoral', 'doacao_privada'], true);
    }

    private static function dataValida(string $valor): bool
    {
        try {
            new \DateTimeImmutable($valor);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function validarTextoObrigatorio(
        array $dados,
        string $campo,
        string $mensagemObrigatoria,
        int $maximo,
        array &$erros,
        bool $cadastro
    ): void {
        $valorInformado = array_key_exists($campo, $dados);
        $valor = trim((string) ($dados[$campo] ?? ''));

        if (($cadastro && !$valorInformado) || ($valorInformado && $valor === '')) {
            $erros[] = $mensagemObrigatoria;
            return;
        }

        if ($valorInformado && mb_strlen($valor) > $maximo) {
            $erros[] = sprintf('O campo %s deve ter no maximo %d caracteres.', $campo, $maximo);
        }
    }
}
