<?php

declare(strict_types=1);

namespace App\Core;

class Requisicao
{
    private array $parametrosRota;
    private array $corpo;
    private array $query;

    public function __construct(array $parametrosRota = [])
    {
        $this->parametrosRota = $parametrosRota;
        $this->query          = $_GET ?? [];
        $this->corpo          = $this->parsearCorpo();
    }

    public function param(string $chave, mixed $padrao = null): mixed
    {
        return $this->parametrosRota[$chave] ?? $padrao;
    }

    public function query(string $chave, mixed $padrao = null): mixed
    {
        return $this->query[$chave] ?? $padrao;
    }

    public function corpo(string $chave, mixed $padrao = null): mixed
    {
        return $this->corpo[$chave] ?? $padrao;
    }

    public function todosCorpo(): array
    {
        return $this->corpo;
    }

    public function auth(?string $chave = null, mixed $padrao = null): mixed
    {
        $auth = $_REQUEST['__auth'] ?? [];

        if ($chave === null) {
            return is_array($auth) ? $auth : [];
        }

        return is_array($auth) ? ($auth[$chave] ?? $padrao) : $padrao;
    }

    public function metodo(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    private function parsearCorpo(): array
    {
        $conteudo = file_get_contents('php://input');

        if (empty($conteudo)) {
            return [];
        }

        $dados = json_decode($conteudo, true);

        return is_array($dados) ? $dados : [];
    }
}
