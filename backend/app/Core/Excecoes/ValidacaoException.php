<?php

declare(strict_types=1);

namespace App\Core\Excecoes;

class ValidacaoException extends \RuntimeException
{
    private array $erros;

    public function __construct(array $erros, string $mensagem = 'Dados inválidos.')
    {
        parent::__construct($mensagem);
        $this->erros = $erros;
    }

    public function getErros(): array
    {
        return $this->erros;
    }
}
