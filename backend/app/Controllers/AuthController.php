<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Services\AuthServico;

class AuthController
{
    private AuthServico $servico;

    public function __construct()
    {
        $this->servico = new AuthServico();
    }

    /**
     * POST /api/auth/login
     * Body: { "cpf": "028.257.332-10", "senha": "..." }
     */
    public function login(Requisicao $req): never
    {
        $cpf   = $req->corpo('cpf')   ?? '';
        $senha = $req->corpo('senha') ?? '';

        $resultado = $this->servico->login((string) $cpf, (string) $senha);

        Resposta::sucesso($resultado, 'Login realizado com sucesso.');
    }
}
