<?php

declare(strict_types=1);

namespace App\Validators;

final class CombustivelValidador
{
    public static function validarCadastro(array $dados): array
    {
        $erros = [];

        if (empty($dados['lider_id'])) {
            $erros[] = 'O líder responsável é obrigatório.';
        }

        self::validarCampos($dados, $erros, true);

        return $erros;
    }

    public static function validarAtualizacao(array $dados): array
    {
        $erros = [];
        self::validarCampos($dados, $erros, false);
        return $erros;
    }

    private static function validarCampos(array $dados, array &$erros, bool $cadastro): void
    {
        self::validarTextoObrigatorio($dados, 'veiculo_descricao', 'Informe a identificacao do veiculo.', 120, $erros, $cadastro);

        if (
            ($cadastro && empty($dados['tipo_combustivel']))
            || (array_key_exists('tipo_combustivel', $dados) && !self::tipoCombustivelValido((string) $dados['tipo_combustivel']))
        ) {
            $erros[] = 'Informe um tipo de combustivel valido: gasolina ou diesel.';
        }

        if (
            ($cadastro && empty($dados['placa_veiculo']))
            || (array_key_exists('placa_veiculo', $dados) && !self::placaValida((string) $dados['placa_veiculo']))
        ) {
            $erros[] = 'Informe uma placa valida com 7 caracteres alfanumericos.';
        }

        self::validarTextoObrigatorio($dados, 'motorista_nome', 'Informe o nome do motorista.', 120, $erros, $cadastro);
        self::validarTextoObrigatorio($dados, 'local_abastecimento', 'Informe o local do abastecimento.', 160, $erros, $cadastro);

        if (
            ($cadastro && !array_key_exists('litros_abastecidos', $dados))
            || (array_key_exists('litros_abastecidos', $dados) && (!is_numeric($dados['litros_abastecidos']) || (float) $dados['litros_abastecidos'] <= 0))
        ) {
            $erros[] = 'A quantidade de litros deve ser numerica e maior que zero.';
        }

        if (
            ($cadastro && !array_key_exists('odometro_atual', $dados))
            || (array_key_exists('odometro_atual', $dados) && (!is_numeric($dados['odometro_atual']) || (float) $dados['odometro_atual'] < 0))
        ) {
            $erros[] = 'O odometro atual deve ser numerico e maior ou igual a zero.';
        }

        if (
            ($cadastro && !array_key_exists('valor_total', $dados))
            || (array_key_exists('valor_total', $dados) && (!is_numeric($dados['valor_total']) || (float) $dados['valor_total'] <= 0))
        ) {
            $erros[] = 'O valor total deve ser numerico e maior que zero.';
        }

        if (
            ($cadastro && empty($dados['data_abastecimento']))
            || (array_key_exists('data_abastecimento', $dados) && !self::dataHoraValida((string) $dados['data_abastecimento']))
        ) {
            $erros[] = 'A data de abastecimento e obrigatoria e deve ser valida.';
        }

        self::validarTextoObrigatorio($dados, 'finalidade', 'Informe a finalidade do abastecimento.', 500, $erros, $cadastro);
        self::validarTextoObrigatorio($dados, 'numero_nota_fiscal', 'Informe o numero da nota fiscal.', 40, $erros, $cadastro);

        if (isset($dados['observacoes']) && mb_strlen(trim((string) $dados['observacoes'])) > 500) {
            $erros[] = 'As observacoes devem ter no maximo 500 caracteres.';
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

    private static function placaValida(string $valor): bool
    {
        $placa = preg_replace('/[^A-Za-z0-9]/', '', mb_strtoupper(trim($valor)));
        return mb_strlen($placa) === 7;
    }

    private static function tipoCombustivelValido(string $valor): bool
    {
        return in_array(mb_strtolower(trim($valor)), ['gasolina', 'diesel'], true);
    }

    private static function dataHoraValida(string $valor): bool
    {
        try {
            new \DateTimeImmutable($valor);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}