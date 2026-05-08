<?php

declare(strict_types=1);

namespace App\Services;

use App\Auxiliares\CpfAuxiliar;
use App\Core\Excecoes\NaoEncontradoException;
use App\Core\Excecoes\ValidacaoException;
use App\Repositories\LiderRepositorio;
use App\Validators\LiderValidador;

class LiderServico
{
    private LiderRepositorio $repositorio;

    public function __construct()
    {
        $this->repositorio = new LiderRepositorio();
    }

    public function listar(int $pagina, int $limite, string $busca): array
    {
        $pagina = max(1, $pagina);
        $limite = min(100, max(1, $limite));

        return $this->repositorio->listar($pagina, $limite, $busca);
    }

    public function buscarPorId(string $id): array
    {
        $lider = $this->repositorio->buscarPorId($id);

        if (!$lider) {
            throw new NaoEncontradoException('Líder');
        }

        return $lider;
    }

    public function cadastrar(array $dados): array
    {
        $erros = LiderValidador::validarCadastro($dados);

        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        $cpfHash = CpfAuxiliar::gerarHash($dados['cpf']);

        if ($this->repositorio->buscarPorCpfHash($cpfHash)) {
            throw new ValidacaoException(['CPF já cadastrado no sistema.']);
        }

        $dados['cpf']      = CpfAuxiliar::criptografar($dados['cpf']);
        $dados['cpf_hash'] = $cpfHash;

        return $this->repositorio->criar($dados);
    }

    public function atualizar(string $id, array $dados): array
    {
        $this->buscarPorId($id);

        $erros = LiderValidador::validarAtualizacao($dados);

        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        $resultado = $this->repositorio->atualizar($id, $dados);

        if (!$resultado) {
            throw new NaoEncontradoException('Líder');
        }

        return $resultado;
    }

    public function remover(string $id): void
    {
        $this->buscarPorId($id);

        if (!$this->repositorio->remover($id)) {
            throw new NaoEncontradoException('Líder');
        }
    }
}
