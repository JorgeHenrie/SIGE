<?php

declare(strict_types=1);

namespace App\Core;

class Roteador
{
    private array $rotas = [];

    public function get(string $caminho, array $acao): void
    {
        $this->registrar('GET', $caminho, $acao);
    }

    public function post(string $caminho, array $acao): void
    {
        $this->registrar('POST', $caminho, $acao);
    }

    public function put(string $caminho, array $acao): void
    {
        $this->registrar('PUT', $caminho, $acao);
    }

    public function delete(string $caminho, array $acao): void
    {
        $this->registrar('DELETE', $caminho, $acao);
    }

    private function registrar(string $metodo, string $caminho, array $acao): void
    {
        $this->rotas[] = [
            'metodo'  => $metodo,
            'caminho' => $caminho,
            'acao'    => $acao,
        ];
    }

    public function despachar(): void
    {
        $metodo = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri    = rtrim($uri, '/') ?: '/';

        foreach ($this->rotas as $rota) {
            $parametros = $this->combinar($rota['metodo'], $rota['caminho'], $metodo, $uri);

            if ($parametros !== null) {
                [$classe, $metodoAcao] = $rota['acao'];
                $controlador = new $classe();
                $controlador->$metodoAcao(new Requisicao($parametros));
                return;
            }
        }

        Resposta::erro('Rota não encontrada.', 404);
    }

    private function combinar(
        string $metodoRota,
        string $caminhoRota,
        string $metodoAtual,
        string $uriAtual
    ): ?array {
        if ($metodoRota !== $metodoAtual) {
            return null;
        }

        $padrao = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $caminhoRota);
        $padrao = '#^' . $padrao . '$#';

        if (!preg_match($padrao, $uriAtual, $matches)) {
            return null;
        }

        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }
}
