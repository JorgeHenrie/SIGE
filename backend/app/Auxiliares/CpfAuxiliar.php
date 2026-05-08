<?php

declare(strict_types=1);

namespace App\Auxiliares;

class CpfAuxiliar
{
    /**
     * Gera HMAC-SHA256 do CPF para verificação de unicidade no banco.
     * Não reversível — usado apenas para constraint UNIQUE.
     */
    public static function gerarHash(string $cpf): string
    {
        $chave = $_ENV['APP_CPF_HMAC_KEY'] ?? '';
        return hash_hmac('sha256', self::apenasNumeros($cpf), $chave);
    }

    /**
     * Criptografa o CPF com AES-256-CBC para armazenamento seguro.
     * IV aleatório concatenado ao dado cifrado, tudo em base64.
     */
    public static function criptografar(string $cpf): string
    {
        $chave  = hash('sha256', $_ENV['APP_CPF_ENCRYPT_KEY'] ?? '', true);
        $iv     = random_bytes(16);
        $cifrado = openssl_encrypt(
            self::apenasNumeros($cpf),
            'AES-256-CBC',
            $chave,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($iv . $cifrado);
    }

    /**
     * Descriptografa o CPF para exibição autorizada.
     */
    public static function descriptografar(string $cpfCifrado): string
    {
        $chave  = hash('sha256', $_ENV['APP_CPF_ENCRYPT_KEY'] ?? '', true);
        $dados  = base64_decode($cpfCifrado);
        $iv     = substr($dados, 0, 16);
        $cifrado = substr($dados, 16);

        return (string) openssl_decrypt(
            $cifrado,
            'AES-256-CBC',
            $chave,
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    /**
     * Valida dígitos verificadores do CPF.
     */
    public static function validar(string $cpf): bool
    {
        $cpf = self::apenasNumeros($cpf);

        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += (int) $cpf[$i] * (10 - $i);
        }
        $resto   = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cpf[9] !== $digito1) {
            return false;
        }

        // Segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += (int) $cpf[$i] * (11 - $i);
        }
        $resto   = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;

        return (int) $cpf[10] === $digito2;
    }

    /**
     * Formata CPF no padrão 000.000.000-00.
     */
    public static function formatar(string $cpf): string
    {
        $cpf = self::apenasNumeros($cpf);
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    public static function apenasNumeros(string $cpf): string
    {
        return preg_replace('/\D/', '', $cpf);
    }
}
