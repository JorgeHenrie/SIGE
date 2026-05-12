<?php

declare(strict_types=1);

namespace App\Services;

use App\Auxiliares\CpfAuxiliar;
use App\Core\Excecoes\AutorizacaoException;
use App\Core\Excecoes\NaoEncontradoException;
use App\Core\Excecoes\ValidacaoException;
use App\Repositories\LiderRepositorio;
use App\Validators\LiderValidador;

class LiderServico
{
    private LiderRepositorio $repositorio;
    private FinanceiroServico $financeiroServico;

    public function __construct()
    {
        $this->repositorio = new LiderRepositorio();
        $this->financeiroServico = new FinanceiroServico();
    }

    public function listar(int $pagina, int $limite, string $busca, array $auth): array
    {
        $pagina = max(1, $pagina);
        $limite = min(100, max(1, $limite));

        $resultado = $this->repositorio->listar($pagina, $limite, $busca);
        $resultado['dados'] = array_map(fn (array $lider): array => $this->sanitizarContrato($lider, $auth), $resultado['dados']);

        return $resultado;
    }

    public function buscarPorId(string $id, array $auth): array
    {
        $lider = $this->repositorio->buscarPorId($id);

        if (!$lider) {
            throw new NaoEncontradoException('Líder');
        }

        return $this->sanitizarContrato($lider, $auth);
    }

    public function cadastrar(array $dados, array $auth): array
    {
        $this->validarContratoNoCadastro($dados, $auth);

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

        if (array_key_exists('salario_mensal', $dados) && $dados['salario_mensal'] !== '' && $dados['salario_mensal'] !== null) {
            $dados['salario_mensal'] = $this->normalizarSalario($dados['salario_mensal']);
        }

        if (array_key_exists('equipe_area', $dados)) {
            $dados['equipe_area'] = $this->normalizarTexto($dados['equipe_area'] ?? null);
        }

        if (array_key_exists('equipe_funcao', $dados)) {
            $dados['equipe_funcao'] = $this->normalizarTexto($dados['equipe_funcao'] ?? null);
        }

        $liderCriado = $this->repositorio->criar($dados);

        try {
            $this->financeiroServico->lancarDespesaPessoalLider([
                'lider_id' => (string) $liderCriado['id'],
                'data' => date('Y-m-d'),
            ], $auth);
        } catch (\Throwable $e) {
            $this->repositorio->remover((string) $liderCriado['id']);

            throw new ValidacaoException([
                'Nao foi possivel concluir o cadastro do lider porque a despesa salarial automatica falhou. Verifique se existe receita com saldo suficiente na campanha.',
            ]);
        }

        return $this->sanitizarContrato($liderCriado, $auth);
    }

    public function atualizar(string $id, array $dados, array $auth): array
    {
        $this->buscarPorId($id, $auth);

        $this->validarContratoNaAtualizacao($dados, $auth);

        $erros = LiderValidador::validarAtualizacao($dados);

        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        if (array_key_exists('salario_mensal', $dados) && $dados['salario_mensal'] !== '' && $dados['salario_mensal'] !== null) {
            $dados['salario_mensal'] = $this->normalizarSalario($dados['salario_mensal']);
        }

        if (array_key_exists('equipe_area', $dados)) {
            $dados['equipe_area'] = $this->normalizarTexto($dados['equipe_area'] ?? null);
        }

        if (array_key_exists('equipe_funcao', $dados)) {
            $dados['equipe_funcao'] = $this->normalizarTexto($dados['equipe_funcao'] ?? null);
        }

        $resultado = $this->repositorio->atualizar($id, $dados);

        if (!$resultado) {
            throw new NaoEncontradoException('Líder');
        }

        return $this->sanitizarContrato($resultado, $auth);
    }

    public function remover(string $id): void
    {
        $this->buscarPorId($id, []);

        if (!$this->repositorio->remover($id)) {
            throw new NaoEncontradoException('Líder');
        }
    }

    private function validarContratoNoCadastro(array $dados, array $auth): void
    {
        if (array_key_exists('salario_mensal', $dados) && !$this->podeGerenciarContrato($auth)) {
            throw new AutorizacaoException('Somente administradores podem definir o salário contratual do líder.');
        }

        if ($this->podeGerenciarContrato($auth) && (!array_key_exists('salario_mensal', $dados) || $dados['salario_mensal'] === '' || $dados['salario_mensal'] === null)) {
            throw new ValidacaoException(['Informe o salário mensal do contrato no cadastro do líder.']);
        }
    }

    private function validarContratoNaAtualizacao(array $dados, array $auth): void
    {
        if (array_key_exists('salario_mensal', $dados) && !$this->podeGerenciarContrato($auth)) {
            throw new AutorizacaoException('Somente administradores podem alterar o salário contratual do líder.');
        }
    }

    private function podeGerenciarContrato(array $auth): bool
    {
        return ($auth['perfil'] ?? null) === 'admin';
    }

    private function sanitizarContrato(array $lider, array $auth): array
    {
        if (!$this->podeGerenciarContrato($auth)) {
            unset($lider['salario_mensal']);
        }

        return $lider;
    }

    private function normalizarSalario(mixed $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }

    private function normalizarTexto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim($valor);
        return $texto === '' ? null : $texto;
    }
}
