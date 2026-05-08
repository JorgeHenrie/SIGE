<?php

declare(strict_types=1);

namespace App\Validators;

use App\Auxiliares\CpfAuxiliar;

class LiderValidador
{
    private const STATUS_VALIDOS = [true, false, 'true', 'false', 1, 0, '1', '0'];

    public static function validarCadastro(array $dados): array
    {
        $erros = [];

        if (empty($dados['nome']) || mb_strlen(trim($dados['nome'])) < 3) {
            $erros[] = 'O campo nome é obrigatório e deve ter no mínimo 3 caracteres.';
        }

        if (mb_strlen(trim($dados['nome'] ?? '')) > 150) {
            $erros[] = 'O campo nome deve ter no máximo 150 caracteres.';
        }

        if (empty($dados['cpf'])) {
            $erros[] = 'O campo CPF é obrigatório.';
        } elseif (!CpfAuxiliar::validar($dados['cpf'])) {
            $erros[] = 'CPF informado é inválido.';
        }

        if (!empty($dados['votos_estimados']) && (!is_numeric($dados['votos_estimados']) || (int) $dados['votos_estimados'] < 0)) {
            $erros[] = 'Votos estimados deve ser um número inteiro não negativo.';
        }

        return $erros;
    }

    public static function validarAtualizacao(array $dados): array
    {
        $erros = [];

        if (isset($dados['nome']) && mb_strlen(trim($dados['nome'])) < 3) {
            $erros[] = 'O campo nome deve ter no mínimo 3 caracteres.';
        }

        if (isset($dados['votos_estimados']) && (!is_numeric($dados['votos_estimados']) || (int) $dados['votos_estimados'] < 0)) {
            $erros[] = 'Votos estimados deve ser um número inteiro não negativo.';
        }

        return $erros;
    }
}
