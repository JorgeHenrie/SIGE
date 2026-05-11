<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GeocodingCacheRepositorio;

class GeocodingServico
{
    private const TENTATIVAS_PROVIDER = 3;
    private const INTERVALO_MINIMO_US = 1100000;

    private static ?int $ultimaRequisicaoMicros = null;

    private GeocodingCacheRepositorio $repositorio;
    private string $provider;

    public function __construct()
    {
        $this->repositorio = new GeocodingCacheRepositorio();
        $this->provider = (string) ($_ENV['ROUTE_GEOCODING_PROVIDER'] ?? 'nominatim');
    }

    public function resolverCoordenadas(string $endereco, mixed $latitude = null, mixed $longitude = null): array
    {
        if ($latitude !== null && $longitude !== null) {
            return [
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                'provider' => 'manual',
                'cache_hit' => true,
            ];
        }

        $enderecoNormalizado = $this->normalizarEndereco($endereco);
        $cache = $this->repositorio->buscarPorEndereco($enderecoNormalizado, $this->provider);

        if ($cache) {
            return [
                'latitude' => (float) $cache['latitude'],
                'longitude' => (float) $cache['longitude'],
                'provider' => (string) $cache['provider'],
                'provider_place_id' => $cache['provider_place_id'] ?? null,
                'score_confianca' => isset($cache['score_confianca']) ? (float) $cache['score_confianca'] : null,
                'cache_hit' => true,
            ];
        }

        $resultado = $this->consultarProvider($endereco);
        $salvo = $this->repositorio->salvar([
            'endereco_original' => trim($endereco),
            'endereco_normalizado' => $enderecoNormalizado,
            'latitude' => $resultado['latitude'],
            'longitude' => $resultado['longitude'],
            'provider' => $resultado['provider'],
            'provider_place_id' => $resultado['provider_place_id'] ?? null,
            'score_confianca' => $resultado['score_confianca'] ?? null,
            'metadados' => $resultado['metadados'] ?? null,
        ]);

        return [
            'latitude' => (float) $salvo['latitude'],
            'longitude' => (float) $salvo['longitude'],
            'provider' => (string) $salvo['provider'],
            'provider_place_id' => $salvo['provider_place_id'] ?? null,
            'score_confianca' => isset($salvo['score_confianca']) ? (float) $salvo['score_confianca'] : null,
            'cache_hit' => false,
        ];
    }

    public function normalizarEndereco(string $endereco): string
    {
        $endereco = trim(mb_strtolower($endereco));
        $endereco = preg_replace('/\s+/', ' ', $endereco) ?? $endereco;
        return $endereco;
    }

    private function consultarProvider(string $endereco): array
    {
        if ($this->provider !== 'nominatim') {
            throw new \RuntimeException('Provider de geocoding não suportado nesta versão.');
        }

        $url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&addressdetails=1&countrycodes=br&q=' . rawurlencode($endereco);
        $userAgent = (string) ($_ENV['ROUTE_GEOCODING_USER_AGENT'] ?? 'SIGE/roteirizacao');
        $timeout = max(2, (int) ($_ENV['ROUTE_GEOCODING_TIMEOUT'] ?? 4));
        $ultimoErro = 'Não foi possível consultar o serviço de geocoding.';

        for ($tentativa = 1; $tentativa <= self::TENTATIVAS_PROVIDER; $tentativa++) {
            $this->aguardarJanelaNominatim();
            $resposta = $this->requisitarComStreams($url, $userAgent, $timeout);

            if ($resposta['corpo'] === null && function_exists('curl_init')) {
                $resposta = $this->requisitarComCurl($url, $userAgent, $timeout);
            }

            if ($resposta['corpo'] === null) {
                $ultimoErro = $resposta['erro'] ?? $ultimoErro;
                continue;
            }

            if ($resposta['status'] === 429 || $resposta['status'] >= 500) {
                $ultimoErro = 'O serviço de geocoding está temporariamente indisponível (rate limit/instabilidade).';
                continue;
            }

            $dados = json_decode($resposta['corpo'], true);
            if (!is_array($dados)) {
                $ultimoErro = 'Resposta inválida recebida do serviço de geocoding.';
                continue;
            }

            if (empty($dados[0]['lat']) || empty($dados[0]['lon'])) {
                throw new \RuntimeException('Nenhuma coordenada válida foi encontrada para o endereço informado.');
            }

            $primeiro = $dados[0];

            return [
                'latitude' => (float) $primeiro['lat'],
                'longitude' => (float) $primeiro['lon'],
                'provider' => 'nominatim',
                'provider_place_id' => isset($primeiro['place_id']) ? (string) $primeiro['place_id'] : null,
                'score_confianca' => isset($primeiro['importance']) ? round((float) $primeiro['importance'] * 100, 2) : null,
                'metadados' => [
                    'display_name' => $primeiro['display_name'] ?? null,
                    'type' => $primeiro['type'] ?? null,
                    'class' => $primeiro['class'] ?? null,
                ],
            ];
        }

        throw new \RuntimeException($ultimoErro . ' Tente novamente em alguns segundos.');
    }

    private function aguardarJanelaNominatim(): void
    {
        $agora = (int) floor(microtime(true) * 1000000);

        if (self::$ultimaRequisicaoMicros !== null) {
            $decorrido = $agora - self::$ultimaRequisicaoMicros;
            if ($decorrido < self::INTERVALO_MINIMO_US) {
                usleep(self::INTERVALO_MINIMO_US - $decorrido);
            }
        }

        self::$ultimaRequisicaoMicros = (int) floor(microtime(true) * 1000000);
    }

    private function requisitarComStreams(string $url, string $userAgent, int $timeout): array
    {
        $contexto = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: {$userAgent}\r\nAccept: application/json\r\n",
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);

        $resposta = @file_get_contents($url, false, $contexto);
        $status = $this->extrairStatusHttp($http_response_header ?? []);

        if ($resposta === false) {
            $erro = error_get_last();
            return [
                'status' => $status,
                'corpo' => null,
                'erro' => $erro['message'] ?? 'Falha de transporte HTTP (streams).',
            ];
        }

        return [
            'status' => $status,
            'corpo' => $resposta,
            'erro' => null,
        ];
    }

    private function requisitarComCurl(string $url, string $userAgent, int $timeout): array
    {
        $ch = curl_init();
        if ($ch === false) {
            return ['status' => 0, 'corpo' => null, 'erro' => 'Não foi possível iniciar cURL.'];
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => [
                "User-Agent: {$userAgent}",
                'Accept: application/json',
            ],
        ]);

        $corpo = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $erro = curl_error($ch);
        curl_close($ch);

        if ($corpo === false || $corpo === null || $corpo === '') {
            return [
                'status' => $status,
                'corpo' => null,
                'erro' => $erro !== '' ? $erro : 'Falha de transporte HTTP (cURL).',
            ];
        }

        return [
            'status' => $status,
            'corpo' => $corpo,
            'erro' => null,
        ];
    }

    private function extrairStatusHttp(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/i', (string) $header, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return 0;
    }
}