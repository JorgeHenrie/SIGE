<?php

declare(strict_types=1);

namespace App\Services;

use App\Auxiliares\CpfAuxiliar;
use App\Core\Excecoes\NaoEncontradoException;
use App\Core\Excecoes\ValidacaoException;
use App\Repositories\ApoiadorRepositorio;
use App\Repositories\LiderRepositorio;
use App\Validators\ApoiadorValidador;

class ApoiadorServico
{
    private ApoiadorRepositorio $repositorio;
    private LiderRepositorio    $liderRepositorio;

    public function __construct()
    {
        $this->repositorio      = new ApoiadorRepositorio();
        $this->liderRepositorio = new LiderRepositorio();
    }

    public function listar(int $pagina, int $limite, string $busca, ?string $liderId = null): array
    {
        $pagina = max(1, $pagina);
        $limite = min(100, max(1, $limite));

        return $this->repositorio->listar($pagina, $limite, $busca, $liderId);
    }

    public function buscarPorId(string $id): array
    {
        $apoiador = $this->repositorio->buscarPorId($id);

        if (!$apoiador) {
            throw new NaoEncontradoException('Apoiador');
        }

        return $apoiador;
    }

    public function cadastrar(array $dados): array
    {
        $erros = ApoiadorValidador::validarCadastro($dados);

        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        // Valida se o líder existe
        if (!$this->liderRepositorio->buscarPorId($dados['lider_id'])) {
            throw new NaoEncontradoException('Líder');
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

        $erros = ApoiadorValidador::validarAtualizacao($dados);

        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        // Se houver troca de líder, valida existência
        if (!empty($dados['lider_id']) && !$this->liderRepositorio->buscarPorId($dados['lider_id'])) {
            throw new NaoEncontradoException('Líder');
        }

        $resultado = $this->repositorio->atualizar($id, $dados);

        if (!$resultado) {
            throw new NaoEncontradoException('Apoiador');
        }

        return $resultado;
    }

    public function remover(string $id): void
    {
        $this->buscarPorId($id);

        if (!$this->repositorio->remover($id)) {
            throw new NaoEncontradoException('Apoiador');
        }
    }
}
