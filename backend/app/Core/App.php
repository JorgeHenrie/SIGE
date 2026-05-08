<?php

declare(strict_types=1);

namespace App\Core;

use App\Auxiliares\JwtAuxiliar;
use App\Core\Excecoes\AutorizacaoException;
use App\Core\Excecoes\NaoEncontradoException;
use App\Core\Excecoes\ValidacaoException;
use Dotenv\Dotenv;

class App
{
    public function inicializar(): void
    {
        $this->carregarVariaveisAmbiente();
        $this->configurarTratamentoErros();
        $this->configurarCabecalhos();
        $this->verificarAutenticacao();
        $this->despacharRota();
    }

    private function carregarVariaveisAmbiente(): void
    {
        $dotenv = Dotenv::createImmutable(BASE_PATH);
        $dotenv->load();

        $dotenv->required([
            'DB_HOST', 'DB_PORT', 'DB_NOME',
            'DB_SCHEMA', 'DB_USUARIO', 'DB_SENHA',
        ]);
    }

    private function configurarTratamentoErros(): void
    {
        set_exception_handler(function (\Throwable $e): void {
            if ($e instanceof ValidacaoException) {
                Resposta::erro($e->getMessage(), 422, $e->getErros());
                return;
            }

            if ($e instanceof AutorizacaoException) {
                Resposta::erro($e->getMessage(), 403);
                return;
            }

            if ($e instanceof NaoEncontradoException) {
                Resposta::erro($e->getMessage(), 404);
                return;
            }

            $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
            $mensagem = $debug ? $e->getMessage() : 'Erro interno do servidor.';
            Resposta::erro($mensagem, 500);
        });
    }

    private function configurarCabecalhos(): void
    {
        $origem = $_ENV['CORS_ORIGIN'] ?? '*';

        header('Content-Type: application/json; charset=utf-8');
        header("Access-Control-Allow-Origin: {$origem}");
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, Accept');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * Verifica JWT em todas as rotas protegidas.
     * Rotas públicas: POST /api/auth/login e OPTIONS (preflight CORS).
     */
    private function verificarAutenticacao(): void
    {
        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

        // Rotas públicas — sem exigir token
        $rotasPublicas = [
            'POST /api/auth/login',
        ];

        $chave = "{$metodo} {$uri}";

        if (in_array($chave, $rotasPublicas, true)) {
            return;
        }

        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!str_starts_with($authHeader, 'Bearer ')) {
            Resposta::erro('Token de autenticação não fornecido.', 401);
        }

        $token   = substr($authHeader, 7);
        $payload = JwtAuxiliar::validar($token);

        if ($payload === null) {
            Resposta::erro('Token inválido ou expirado.', 401);
        }

        // Disponibiliza dados do usuário autenticado para os controllers
        $_REQUEST['__auth'] = $payload;
    }

    private function despacharRota(): void
    {
        $roteador = new Roteador();
        require_once BASE_PATH . '/routes/api.php';
        $roteador->despachar();
    }
}
