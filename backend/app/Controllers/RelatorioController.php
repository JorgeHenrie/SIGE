<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Requisicao;
use App\Core\Resposta;
use App\Services\RelatorioServico;

class RelatorioController
{
    private RelatorioServico $servico;

    public function __construct()
    {
        $this->servico = new RelatorioServico();
    }

    public function resumo(Requisicao $req): void
    {
        $dados = $this->servico->resumo();
        Resposta::sucesso($dados);
    }

    public function porLider(Requisicao $req): void
    {
        $dados = $this->servico->porLider();
        Resposta::sucesso($dados);
    }

    public function porBairro(Requisicao $req): void
    {
        $dados = $this->servico->porBairro();
        Resposta::sucesso($dados);
    }

    public function consolidado(Requisicao $req): void
    {
        $dados = $this->servico->consolidado();
        Resposta::sucesso($dados);
    }
}
