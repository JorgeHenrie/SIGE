<?php

declare(strict_types=1);

namespace App\Core\Excecoes;

class AutorizacaoException extends \RuntimeException
{
    public function __construct(string $message = 'Acesso negado.')
    {
        parent::__construct($message);
    }
}