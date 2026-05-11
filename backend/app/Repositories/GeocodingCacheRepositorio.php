<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Banco;
use PDO;

class GeocodingCacheRepositorio
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Banco::conexao();
    }

    public function buscarPorEndereco(string $enderecoNormalizado, string $provider): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT *
            FROM sige.geocoding_cache
            WHERE endereco_normalizado = :endereco_normalizado
              AND provider = :provider
            LIMIT 1
        ');
        $stmt->execute([
            ':endereco_normalizado' => $enderecoNormalizado,
            ':provider' => $provider,
        ]);

        $resultado = $stmt->fetch();
        return $resultado ?: null;
    }

    public function salvar(array $dados): array
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO sige.geocoding_cache (
                endereco_original,
                endereco_normalizado,
                latitude,
                longitude,
                provider,
                provider_place_id,
                score_confianca,
                metadados
            ) VALUES (
                :endereco_original,
                :endereco_normalizado,
                :latitude,
                :longitude,
                :provider,
                :provider_place_id,
                :score_confianca,
                :metadados
            )
            ON CONFLICT (endereco_normalizado, provider)
            DO UPDATE SET
                endereco_original = EXCLUDED.endereco_original,
                latitude = EXCLUDED.latitude,
                longitude = EXCLUDED.longitude,
                provider_place_id = EXCLUDED.provider_place_id,
                score_confianca = EXCLUDED.score_confianca,
                metadados = EXCLUDED.metadados
            RETURNING *
        ');

        $stmt->execute([
            ':endereco_original' => $dados['endereco_original'],
            ':endereco_normalizado' => $dados['endereco_normalizado'],
            ':latitude' => $dados['latitude'],
            ':longitude' => $dados['longitude'],
            ':provider' => $dados['provider'],
            ':provider_place_id' => $dados['provider_place_id'] ?? null,
            ':score_confianca' => $dados['score_confianca'] ?? null,
            ':metadados' => isset($dados['metadados'])
                ? json_encode($dados['metadados'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,
        ]);

        return (array) $stmt->fetch();
    }
}