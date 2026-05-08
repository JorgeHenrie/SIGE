<?php

declare(strict_types=1);

namespace App\Validators;

use App\Auxiliares\CpfAuxiliar;

class ApoiadorValidador
{
    private const STATUS_VALIDOS = ['apoiador', 'indeciso', 'oposicao'];

    public static function validarCadastro(array $dados): array
    {
        $erros = [];

        if (empty($dados['lider_id'])) {
            $erros[] = 'O campo líder é obrigatório.';
        }

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

        if (!empty($dados['status_politico']) && !in_array($dados['status_politico'], self::STATUS_VALIDOS, true)) {
            $erros[] = 'Status político inválido. Valores aceitos: apoiador, indeciso, oposicao.';
        }

        return $erros;
    }

    public static function validarAtualizacao(array $dados): array
    {
        $erros = [];

        if (isset($dados['nome']) && mb_strlen(trim($dados['nome'])) < 3) {
            $erros[] = 'O campo nome deve ter no mínimo 3 caracteres.';
        }

        if (isset($dados['status_politico']) && !in_array($dados['status_politico'], self::STATUS_VALIDOS, true)) {
            $erros[] = 'Status político inválido. Valores aceitos: apoiador, indeciso, oposicao.';
        }

        return $erros;
    }
}
