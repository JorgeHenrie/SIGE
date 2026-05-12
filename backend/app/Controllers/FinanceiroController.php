<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Services\FinanceiroServico;

class FinanceiroController
{
    private FinanceiroServico $servico;

    public function __construct()
    {
        $this->servico = new FinanceiroServico();
    }

    public function listarFornecedores(Requisicao $req): void
    {
        $pagina = (int) $req->query('pagina', 1);
        $limite = (int) $req->query('limite', 15);
        $busca = (string) $req->query('busca', '');

        $resultado = $this->servico->listarFornecedores($pagina, $limite, $busca, $req->auth());

        Resposta::paginado($resultado['dados'], $resultado['total'], $pagina, $limite);
    }

    public function visualizarFornecedor(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $dados = $this->servico->buscarFornecedorPorId($id, $req->auth());

        Resposta::sucesso($dados);
    }

    public function cadastrarFornecedor(Requisicao $req): void
    {
        $dados = $this->servico->cadastrarFornecedor($req->todosCorpo(), $req->auth());
        Resposta::criado($dados, 'Fornecedor cadastrado com sucesso.');
    }

    public function atualizarFornecedor(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $dados = $this->servico->atualizarFornecedor($id, $req->todosCorpo(), $req->auth());

        Resposta::sucesso($dados, 'Fornecedor atualizado com sucesso.');
    }

    public function removerFornecedor(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $this->servico->removerFornecedor($id, $req->auth());

        Resposta::sucesso(null, 'Fornecedor removido com sucesso.');
    }

    public function listarReceitas(Requisicao $req): void
    {
        $pagina = (int) $req->query('pagina', 1);
        $limite = (int) $req->query('limite', 15);
        $busca = (string) $req->query('busca', '');

        $resultado = $this->servico->listarReceitas($pagina, $limite, $busca, $req->auth());

        Resposta::paginado($resultado['dados'], $resultado['total'], $pagina, $limite);
    }

    public function visualizarReceita(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $dados = $this->servico->buscarReceitaPorId($id, $req->auth());

        Resposta::sucesso($dados);
    }

    public function cadastrarReceita(Requisicao $req): void
    {
        $dados = $this->servico->cadastrarReceita($req->todosCorpo(), $req->auth());
        Resposta::criado($dados, 'Receita cadastrada com sucesso.');
    }

    public function atualizarReceita(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $dados = $this->servico->atualizarReceita($id, $req->todosCorpo(), $req->auth());

        Resposta::sucesso($dados, 'Receita atualizada com sucesso.');
    }

    public function removerReceita(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $this->servico->removerReceita($id, $req->auth());

        Resposta::sucesso(null, 'Receita removida com sucesso.');
    }

    public function listarDespesas(Requisicao $req): void
    {
        $pagina = (int) $req->query('pagina', 1);
        $limite = (int) $req->query('limite', 15);
        $busca = (string) $req->query('busca', '');

        $resultado = $this->servico->listarDespesas($pagina, $limite, $busca, $req->auth());

        Resposta::paginado($resultado['dados'], $resultado['total'], $pagina, $limite);
    }

    public function visualizarDespesa(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $dados = $this->servico->buscarDespesaPorId($id, $req->auth());

        Resposta::sucesso($dados);
    }

    public function cadastrarDespesa(Requisicao $req): void
    {
        $dados = $this->servico->cadastrarDespesa($req->todosCorpo(), $req->auth());
        Resposta::criado($dados, 'Despesa cadastrada com sucesso.');
    }

    public function lancarDespesaPessoalLider(Requisicao $req): void
    {
        $dados = $this->servico->lancarDespesaPessoalLider($req->todosCorpo(), $req->auth());
        Resposta::criado($dados, 'Despesa salarial de lider lancada com sucesso.');
    }

    public function atualizarDespesa(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $dados = $this->servico->atualizarDespesa($id, $req->todosCorpo(), $req->auth());

        Resposta::sucesso($dados, 'Despesa atualizada com sucesso.');
    }

    public function removerDespesa(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $this->servico->removerDespesa($id, $req->auth());

        Resposta::sucesso(null, 'Despesa removida com sucesso.');
    }

    public function saldos(Requisicao $req): void
    {
        $candidatoId = $req->query('candidato_id');
        $dados = $this->servico->saldos($req->auth(), $candidatoId !== null ? (string) $candidatoId : null);

        Resposta::sucesso($dados);
    }

    public function relatorioInteligente(Requisicao $req): void
    {
        $dados = $this->servico->relatorioInteligente(
            $req->auth(),
            $req->query('candidato_id') !== null ? (string) $req->query('candidato_id') : null,
            $req->query('data_inicio') !== null ? (string) $req->query('data_inicio') : null,
            $req->query('data_fim') !== null ? (string) $req->query('data_fim') : null
        );

        Resposta::sucesso($dados);
    }

    public function alertas(Requisicao $req): void
    {
        $dados = $this->servico->alertas(
            $req->auth(),
            $req->query('candidato_id') !== null ? (string) $req->query('candidato_id') : null
        );

        Resposta::sucesso($dados);
    }

    public function auditoria(Requisicao $req): void
    {
        $dados = $this->servico->auditoria(
            $req->auth(),
            $req->query('candidato_id') !== null ? (string) $req->query('candidato_id') : null,
            $req->query('data_inicio') !== null ? (string) $req->query('data_inicio') : null,
            $req->query('data_fim') !== null ? (string) $req->query('data_fim') : null
        );

        Resposta::sucesso($dados);
    }

    public function prestacaoContas(Requisicao $req): void
    {
        $dados = $this->servico->prepararPrestacaoContas(
            $req->auth(),
            $req->query('candidato_id') !== null ? (string) $req->query('candidato_id') : null,
            $req->query('data_inicio') !== null ? (string) $req->query('data_inicio') : null,
            $req->query('data_fim') !== null ? (string) $req->query('data_fim') : null
        );

        Resposta::sucesso($dados);
    }
}
