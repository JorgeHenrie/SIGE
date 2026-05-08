<?php

declare(strict_types=1);

namespace App\Models;

class Lider
{
    public function __construct(
        public readonly ?string $id,
        public readonly string  $nome,
        public readonly string  $cpf,
        public readonly string  $cpfHash,
        public readonly ?string $telefone,
        public readonly ?string $bairro,
        public readonly int     $votosEstimados,
        public readonly ?string $observacoes,
        public readonly bool    $status,
        public readonly ?string $criadoPor,
        public readonly ?string $criadoEm,
        public readonly ?string $atualizadoEm,
    ) {}

    public static function fromArray(array $dados): self
    {
        return new self(
            id:             $dados['id']              ?? null,
            nome:           $dados['nome']            ?? '',
            cpf:            $dados['cpf']             ?? '',
            cpfHash:        $dados['cpf_hash']        ?? '',
            telefone:       $dados['telefone']        ?? null,
            bairro:         $dados['bairro']          ?? null,
            votosEstimados: (int) ($dados['votos_estimados'] ?? 0),
            observacoes:    $dados['observacoes']     ?? null,
            status:         (bool) ($dados['status'] ?? true),
            criadoPor:      $dados['criado_por']      ?? null,
            criadoEm:       $dados['criado_em']       ?? null,
            atualizadoEm:   $dados['atualizado_em']   ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'nome'            => $this->nome,
            'cpf_hash'        => $this->cpfHash,
            'telefone'        => $this->telefone,
            'bairro'          => $this->bairro,
            'votos_estimados' => $this->votosEstimados,
            'observacoes'     => $this->observacoes,
            'status'          => $this->status,
            'criado_por'      => $this->criadoPor,
            'criado_em'       => $this->criadoEm,
            'atualizado_em'   => $this->atualizadoEm,
        ];
    }
}
