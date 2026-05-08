<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Services\AgendaServico;

class AgendaController
{
    private AgendaServico $servico;

    public function __construct()
    {
        $this->servico = new AgendaServico();
    }

    public function listar(Requisicao $req): void
    {
        $pagina = (int) $req->query('pagina', 1);
        $limite = (int) $req->query('limite', 15);
        $busca = (string) $req->query('busca', '');
        $status = (string) $req->query('status', '');

        $resultado = $this->servico->listar($pagina, $limite, $busca, $status ?: null, $req->auth());

        Resposta::paginado($resultado['dados'], $resultado['total'], $pagina, $limite);
    }

    public function visualizar(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $dados = $this->servico->buscarPorId($id, $req->auth());

        Resposta::sucesso($dados);
    }

    public function cadastrar(Requisicao $req): void
    {
        $dados = $this->servico->cadastrar($req->todosCorpo(), $req->auth());
        Resposta::criado($dados, 'Solicitação de agenda criada com sucesso.');
    }

    public function atualizar(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $dados = $this->servico->atualizar($id, $req->todosCorpo(), $req->auth());
        Resposta::sucesso($dados, 'Solicitação de agenda atualizada com sucesso.');
    }

    public function aprovar(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $dados = $this->servico->aprovar($id, $req->todosCorpo(), $req->auth());
        Resposta::sucesso($dados, 'Solicitação aprovada com sucesso.');
    }

    public function recusar(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $dados = $this->servico->recusar($id, $req->todosCorpo(), $req->auth());
        Resposta::sucesso($dados, 'Solicitação recusada com sucesso.');
    }

    public function remover(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $this->servico->remover($id, $req->auth());
        Resposta::sucesso(null, 'Solicitação removida com sucesso.');
    }
}