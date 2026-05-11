<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Services\RoteirizacaoServico;

class RoteiroController
{
    private RoteirizacaoServico $servico;

    public function __construct()
    {
        $this->servico = new RoteirizacaoServico();
    }

    public function listar(Requisicao $req): void
    {
        $pagina = (int) $req->query('pagina', 1);
        $limite = (int) $req->query('limite', 15);
        $busca = (string) $req->query('busca', '');
        $liderId = (string) $req->query('lider_id', '');

        $resultado = $this->servico->listar($pagina, $limite, $busca, $liderId ?: null, $req->auth());

        Resposta::paginado($resultado['dados'], $resultado['total'], $pagina, $limite);
    }

    public function visualizar(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $dados = $this->servico->buscarPorId($id, $req->auth());

        Resposta::sucesso($dados);
    }

    public function sugerir(Requisicao $req): void
    {
        $dados = $this->servico->sugerir($req->todosCorpo(), $req->auth());
        Resposta::sucesso($dados, 'Roteiro sugerido com sucesso.');
    }

    public function cadastrar(Requisicao $req): void
    {
        $dados = $this->servico->cadastrar($req->todosCorpo(), $req->auth());
        Resposta::criado($dados, 'Roteiro criado com sucesso.');
    }

    public function recalcular(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $dados = $this->servico->recalcular($id, $req->todosCorpo(), $req->auth());
        Resposta::sucesso($dados, 'Roteiro recalculado com sucesso.');
    }

    public function remover(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $this->servico->remover($id, $req->auth());
        Resposta::sucesso(null, 'Roteiro removido com sucesso.');
    }
}