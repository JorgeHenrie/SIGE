<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Banco
{
    private static ?PDO $conexao = null;

    private function __construct() {}

    public static function conexao(): PDO
    {
        if (self::$conexao === null) {
            self::$conexao = self::criarConexao();
        }

        return self::$conexao;
    }

    private static function criarConexao(): PDO
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;options=--search_path=%s',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'],
            $_ENV['DB_NOME'],
            $_ENV['DB_SCHEMA']
        );

        try {
            return new PDO($dsn, $_ENV['DB_USUARIO'], $_ENV['DB_SENHA'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(503);
            echo json_encode([
                'sucesso'  => false,
                'mensagem' => 'Serviço de banco de dados indisponível.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
