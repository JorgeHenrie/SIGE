<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Services\CombustivelServico;

class CombustivelController
{
    private CombustivelServico $servico;

    public function __construct()
    {
        $this->servico = new CombustivelServico();
    }

    public function listar(Requisicao $req): void
    {
        $pagina = (int) $req->query('pagina', 1);
        $limite = (int) $req->query('limite', 15);
        $busca = (string) $req->query('busca', '');

        $resultado = $this->servico->listar($pagina, $limite, $busca, $req->auth());

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
        Resposta::criado($dados, 'Abastecimento lançado com sucesso.');
    }

    public function atualizar(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $dados = $this->servico->atualizar($id, $req->todosCorpo(), $req->auth());
        Resposta::sucesso($dados, 'Abastecimento atualizado com sucesso.');
    }

    public function remover(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $this->servico->remover($id, $req->auth());
        Resposta::sucesso(null, 'Abastecimento removido com sucesso.');
    }
}