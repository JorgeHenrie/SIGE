<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\RelatorioRepositorio;

class RelatorioServico
{
    private RelatorioRepositorio $repositorio;

    public function __construct()
    {
        $this->repositorio = new RelatorioRepositorio();
    }

    public function resumo(): array
    {
        return $this->repositorio->resumo();
    }

    public function porLider(): array
    {
        return $this->repositorio->porLider();
    }

    public function porBairro(): array
    {
        return $this->repositorio->porBairro();
    }

    public function consolidado(): array
    {
        return $this->repositorio->consolidado();
    }
}
