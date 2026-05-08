<?php

declare(strict_types=1);

namespace App\Auxiliares;

/**
 * Utilitário JWT HS256 sem dependências externas.
 *
 * Geração: JwtAuxiliar::gerar(['sub' => uuid, ...])
 * Validação: JwtAuxiliar::validar($token) → payload array ou null
 */
class JwtAuxiliar
{
    private const ALGORITMO = 'sha256';
    private const VALIDADE   = 28800; // 8 horas em segundos

    // ----------------------------------------------------------
    // Gera um token JWT HS256 assinado com APP_JWT_SECRET
    // ----------------------------------------------------------
    public static function gerar(array $payload): string
    {
        $header = self::base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));

        $payload['iat'] = time();
        $payload['exp'] = time() + self::VALIDADE;

        $body = self::base64url(json_encode($payload));

        $assinatura = self::base64url(
            hash_hmac(self::ALGORITMO, "{$header}.{$body}", self::segredo(), true)
        );

        return "{$header}.{$body}.{$assinatura}";
    }

    // ----------------------------------------------------------
    // Valida o token e retorna o payload ou null se inválido/expirado
    // ----------------------------------------------------------
    public static function validar(string $token): ?array
    {
        $partes = explode('.', $token);

        if (count($partes) !== 3) {
            return null;
        }

        [$header, $body, $assinaturaDada] = $partes;

        $assinaturaEsperada = self::base64url(
            hash_hmac(self::ALGORITMO, "{$header}.{$body}", self::segredo(), true)
        );

        // Comparação resistente a timing attacks
        if (!hash_equals($assinaturaEsperada, $assinaturaDada)) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($body, '-_', '+/')), true);

        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    // ----------------------------------------------------------
    // Lê o segredo do ambiente, com fallback seguro
    // ----------------------------------------------------------
    private static function segredo(): string
    {
        $s = $_ENV['APP_JWT_SECRET'] ?? '';

        if (strlen($s) < 32) {
            throw new \RuntimeException('APP_JWT_SECRET ausente ou muito curto (mínimo 32 caracteres).');
        }

        return $s;
    }

    // ----------------------------------------------------------
    // Codificação Base64URL (sem padding)
    // ----------------------------------------------------------
    private static function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
