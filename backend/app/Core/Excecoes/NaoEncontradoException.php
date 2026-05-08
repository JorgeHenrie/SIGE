<?php

declare(strict_types=1);

namespace App\Core\Excecoes;

class NaoEncontradoException extends \RuntimeException
{
    public function __construct(string $entidade = 'Registro')
    {
        parent::__construct("{$entidade} não encontrado.");
    }
}
