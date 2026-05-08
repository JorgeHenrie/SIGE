<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Services\LiderServico;

class LiderController
{
    private LiderServico $servico;

    public function __construct()
    {
        $this->servico = new LiderServico();
    }

    public function listar(Requisicao $req): void
    {
        $pagina = (int) $req->query('pagina', 1);
        $limite = (int) $req->query('limite', 15);
        $busca  = (string) $req->query('busca', '');

        $resultado = $this->servico->listar($pagina, $limite, $busca);

        Resposta::paginado(
            $resultado['dados'],
            $resultado['total'],
            $pagina,
            $limite
        );
    }

    public function visualizar(Requisicao $req): void
    {
        $id    = (string) $req->param('id');
        $lider = $this->servico->buscarPorId($id);

        Resposta::sucesso($lider);
    }

    public function cadastrar(Requisicao $req): void
    {
        $dados = $req->todosCorpo();
        $lider = $this->servico->cadastrar($dados);

        Resposta::criado($lider, 'Líder cadastrado com sucesso.');
    }

    public function atualizar(Requisicao $req): void
    {
        $id    = (string) $req->param('id');
        $dados = $req->todosCorpo();
        $lider = $this->servico->atualizar($id, $dados);

        Resposta::sucesso($lider, 'Líder atualizado com sucesso.');
    }

    public function remover(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $this->servico->remover($id);

        Resposta::sucesso(null, 'Líder removido com sucesso.');
    }
}
