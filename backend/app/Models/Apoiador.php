<?php

declare(strict_types=1);

namespace App\Models;

class Apoiador
{
    public function __construct(
        public readonly ?string $id,
        public readonly string  $liderId,
        public readonly string  $nome,
        public readonly string  $cpf,
        public readonly string  $cpfHash,
        public readonly ?string $telefone,
        public readonly ?string $bairro,
        public readonly string  $statusPolitico,
        public readonly ?string $observacoes,
        public readonly ?string $criadoPor,
        public readonly ?string $criadoEm,
        public readonly ?string $atualizadoEm,
    ) {}

    public static function fromArray(array $dados): self
    {
        return new self(
            id:             $dados['id']               ?? null,
            liderId:        $dados['lider_id']         ?? '',
            nome:           $dados['nome']             ?? '',
            cpf:            $dados['cpf']              ?? '',
            cpfHash:        $dados['cpf_hash']         ?? '',
            telefone:       $dados['telefone']         ?? null,
            bairro:         $dados['bairro']           ?? null,
            statusPolitico: $dados['status_politico']  ?? 'indeciso',
            observacoes:    $dados['observacoes']      ?? null,
            criadoPor:      $dados['criado_por']       ?? null,
            criadoEm:       $dados['criado_em']        ?? null,
            atualizadoEm:   $dados['atualizado_em']    ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'lider_id'        => $this->liderId,
            'nome'            => $this->nome,
            'cpf_hash'        => $this->cpfHash,
            'telefone'        => $this->telefone,
            'bairro'          => $this->bairro,
            'status_politico' => $this->statusPolitico,
            'observacoes'     => $this->observacoes,
            'criado_por'      => $this->criadoPor,
            'criado_em'       => $this->criadoEm,
            'atualizado_em'   => $this->atualizadoEm,
        ];
    }
}
