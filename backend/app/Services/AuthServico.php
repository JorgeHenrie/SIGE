<?php

declare(strict_types=1);

namespace App\Services;

use App\Auxiliares\CpfAuxiliar;
use App\Auxiliares\JwtAuxiliar;
use App\Core\Banco;
use App\Core\Excecoes\ValidacaoException;

class AuthServico
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Banco::conexao();
    }

    /**
     * Autentica via CPF + senha.
     * Retorna ['token' => string, 'usuario' => array].
     *
     * @throws ValidacaoException se credenciais inválidas
     */
    public function login(string $cpf, string $senha): array
    {
        $cpf = CpfAuxiliar::apenasNumeros($cpf);

        // Valida formato básico antes de qualquer query
        if (!CpfAuxiliar::validar($cpf)) {
            throw new ValidacaoException('CPF inválido.', ['cpf' => 'CPF inválido.']);
        }

        $cpfHash = CpfAuxiliar::gerarHash($cpf);

        // Busca credenciais na view unificada
        $stmt = $this->db->prepare(
            'SELECT id, nome, senha_hash, perfil, tipo
             FROM sige.vw_credenciais
             WHERE cpf_hash = :cpf_hash
             LIMIT 1'
        );
        $stmt->execute([':cpf_hash' => $cpfHash]);
        $credencial = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Mensagem genérica — não revela se CPF existe ou não
        $erroGenerico = 'CPF ou senha incorretos.';

        if (!$credencial) {
            throw new ValidacaoException($erroGenerico, ['cpf' => $erroGenerico]);
        }

        if (!password_verify($senha, $credencial['senha_hash'])) {
            throw new ValidacaoException($erroGenerico, ['cpf' => $erroGenerico]);
        }

        $token = JwtAuxiliar::gerar([
            'sub'    => $credencial['id'],
            'nome'   => $credencial['nome'],
            'perfil' => $credencial['perfil'],
            'tipo'   => $credencial['tipo'],
        ]);

        return [
            'token'  => $token,
            'usuario' => [
                'id'     => $credencial['id'],
                'nome'   => $credencial['nome'],
                'perfil' => $credencial['perfil'],
                'tipo'   => $credencial['tipo'],
            ],
        ];
    }
}
