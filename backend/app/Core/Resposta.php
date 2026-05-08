<?php

declare(strict_types=1);

namespace App\Core;

class Resposta
{
    public static function json(array $dados, int $status = 200): never
    {
        http_response_code($status);
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function sucesso(mixed $dados = null, string $mensagem = 'Operação realizada com sucesso.', int $status = 200): never
    {
        $resposta = [
            'sucesso'  => true,
            'mensagem' => $mensagem,
        ];

        if ($dados !== null) {
            $resposta['dados'] = $dados;
        }

        self::json($resposta, $status);
    }

    public static function paginado(array $dados, int $total, int $pagina, int $limite, string $mensagem = 'Operação realizada com sucesso.'): never
    {
        self::json([
            'sucesso'   => true,
            'mensagem'  => $mensagem,
            'dados'     => $dados,
            'paginacao' => [
                'pagina_atual'  => $pagina,
                'por_pagina'    => $limite,
                'total'         => $total,
                'total_paginas' => (int) ceil($total / $limite),
            ],
        ]);
    }

    public static function criado(mixed $dados = null, string $mensagem = 'Registro criado com sucesso.'): never
    {
        self::sucesso($dados, $mensagem, 201);
    }

    public static function erro(string $mensagem, int $status = 400, array $erros = []): never
    {
        $resposta = [
            'sucesso'  => false,
            'mensagem' => $mensagem,
        ];

        if (!empty($erros)) {
            $resposta['erros'] = $erros;
        }

        self::json($resposta, $status);
    }

    public static function semConteudo(): never
    {
        http_response_code(204);
        exit;
    }
}
