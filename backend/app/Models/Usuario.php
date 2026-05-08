<?php

declare(strict_types=1);

namespace App\Models;

class Usuario
{
    public function __construct(
        public readonly ?string $id,
        public readonly string  $nome,
        public readonly string  $email,
        public readonly string  $perfil,
        public readonly bool    $status,
        public readonly ?string $criadoEm,
        public readonly ?string $atualizadoEm,
    ) {}

    public static function fromArray(array $dados): self
    {
        return new self(
            id:           $dados['id']            ?? null,
            nome:         $dados['nome']          ?? '',
            email:        $dados['email']         ?? '',
            perfil:       $dados['perfil']        ?? 'admin',
            status:       (bool) ($dados['status'] ?? true),
            criadoEm:     $dados['criado_em']     ?? null,
            atualizadoEm: $dados['atualizado_em'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'nome'         => $this->nome,
            'email'        => $this->email,
            'perfil'       => $this->perfil,
            'status'       => $this->status,
            'criado_em'    => $this->criadoEm,
            'atualizado_em' => $this->atualizadoEm,
        ];
    }
}
