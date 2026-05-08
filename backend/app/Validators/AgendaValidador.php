<?php

declare(strict_types=1);

namespace App\Validators;

final class AgendaValidador
{
    private const TIPOS_VALIDOS = ['visita', 'reuniao', 'outro'];

    public static function validarCadastro(array $dados): array
    {
        $erros = [];

        if (empty($dados['lider_id'])) {
            $erros[] = 'O líder responsável é obrigatório.';
        }

        self::validarCamposBasicos($dados, $erros, true);

        return $erros;
    }

    public static function validarAtualizacao(array $dados): array
    {
        $erros = [];
        self::validarCamposBasicos($dados, $erros, false);
        return $erros;
    }

    public static function validarAprovacao(array $dados): array
    {
        $erros = [];

        if (empty($dados['data_confirmada_inicio'])) {
            $erros[] = 'A data confirmada de início é obrigatória para aprovar.';
        } elseif (!self::dataHoraValida((string) $dados['data_confirmada_inicio'])) {
            $erros[] = 'A data confirmada de início é inválida.';
        }

        if (!empty($dados['data_confirmada_fim']) && !self::dataHoraValida((string) $dados['data_confirmada_fim'])) {
            $erros[] = 'A data confirmada de fim é inválida.';
        }

        if (
            !empty($dados['data_confirmada_inicio'])
            && !empty($dados['data_confirmada_fim'])
            && strtotime((string) $dados['data_confirmada_fim']) < strtotime((string) $dados['data_confirmada_inicio'])
        ) {
            $erros[] = 'A data confirmada de fim deve ser maior ou igual à data confirmada de início.';
        }

        return $erros;
    }

    public static function validarRecusa(array $dados): array
    {
        $erros = [];

        if (empty($dados['observacoes_decisao']) || mb_strlen(trim((string) $dados['observacoes_decisao'])) < 5) {
            $erros[] = 'Informe o motivo da recusa com pelo menos 5 caracteres.';
        }

        return $erros;
    }

    private static function validarCamposBasicos(array $dados, array &$erros, bool $cadastro): void
    {
        if (($cadastro && empty($dados['titulo'])) || (isset($dados['titulo']) && mb_strlen(trim((string) $dados['titulo'])) < 3)) {
            $erros[] = 'O título é obrigatório e deve ter no mínimo 3 caracteres.';
        }

        if (isset($dados['titulo']) && mb_strlen(trim((string) $dados['titulo'])) > 160) {
            $erros[] = 'O título deve ter no máximo 160 caracteres.';
        }

        if (!empty($dados['tipo']) && !in_array($dados['tipo'], self::TIPOS_VALIDOS, true)) {
            $erros[] = 'Tipo de evento inválido. Valores aceitos: visita, reuniao, outro.';
        }

        if (($cadastro && empty($dados['data_solicitada_inicio'])) || (isset($dados['data_solicitada_inicio']) && !self::dataHoraValida((string) $dados['data_solicitada_inicio']))) {
            $erros[] = 'A data solicitada de início é obrigatória e deve ser válida.';
        }

        if (!empty($dados['data_solicitada_fim']) && !self::dataHoraValida((string) $dados['data_solicitada_fim'])) {
            $erros[] = 'A data solicitada de fim é inválida.';
        }

        if (
            !empty($dados['data_solicitada_inicio'])
            && !empty($dados['data_solicitada_fim'])
            && strtotime((string) $dados['data_solicitada_fim']) < strtotime((string) $dados['data_solicitada_inicio'])
        ) {
            $erros[] = 'A data solicitada de fim deve ser maior ou igual à data solicitada de início.';
        }

        if (!empty($dados['local_evento']) && mb_strlen(trim((string) $dados['local_evento'])) > 180) {
            $erros[] = 'O local do evento deve ter no máximo 180 caracteres.';
        }
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