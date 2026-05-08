<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Services\ApoiadorServico;

class ApoiadorController
{
    private ApoiadorServico $servico;

    public function __construct()
    {
        $this->servico = new ApoiadorServico();
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

    public function listarPorLider(Requisicao $req): void
    {
        $liderId = (string) $req->param('id');
        $pagina  = (int) $req->query('pagina', 1);
        $limite  = (int) $req->query('limite', 15);
        $busca   = (string) $req->query('busca', '');

        $resultado = $this->servico->listar($pagina, $limite, $busca, $liderId);

        Resposta::paginado(
            $resultado['dados'],
            $resultado['total'],
            $pagina,
            $limite
        );
    }

    public function visualizar(Requisicao $req): void
    {
        $id       = (string) $req->param('id');
        $apoiador = $this->servico->buscarPorId($id);

        Resposta::sucesso($apoiador);
    }

    public function cadastrar(Requisicao $req): void
    {
        $dados    = $req->todosCorpo();
        $apoiador = $this->servico->cadastrar($dados);

        Resposta::criado($apoiador, 'Apoiador cadastrado com sucesso.');
    }

    public function atualizar(Requisicao $req): void
    {
        $id       = (string) $req->param('id');
        $dados    = $req->todosCorpo();
        $apoiador = $this->servico->atualizar($id, $dados);

        Resposta::sucesso($apoiador, 'Apoiador atualizado com sucesso.');
    }

    public function remover(Requisicao $req): void
    {
        $id = (string) $req->param('id');
        $this->servico->remover($id);

        Resposta::sucesso(null, 'Apoiador removido com sucesso.');
    }
}
