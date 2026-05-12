<?php

declare(strict_types=1);

namespace App\Validators;

final class RoteiroValidador
{
    private const TRANSPORTES_VALIDOS = ['carro', 'moto', 'a_pe'];
    private const PRIORIDADES_VALIDAS = ['alta', 'media', 'baixa'];
    private const MINIMO_COMPROMISSOS_OTIMIZACAO = 2;

    public static function validarPlanejamento(array $dados): array
    {
        $erros = [];

        if (empty($dados['lider_id'])) {
            $erros['lider_id'] = 'O líder responsável é obrigatório.';
        }

        if (empty($dados['data_roteiro']) || !self::dataValida((string) $dados['data_roteiro'])) {
            $erros['data_roteiro'] = 'Informe uma data de roteiro válida no formato AAAA-MM-DD.';
        }

        $localSaida = trim((string) ($dados['local_saida'] ?? ''));
        $origemLatitudeInformada = array_key_exists('local_saida_latitude', $dados);
        $origemLongitudeInformada = array_key_exists('local_saida_longitude', $dados);

        if ($localSaida === '' && !($origemLatitudeInformada && $origemLongitudeInformada)) {
            $erros['local_saida'] = 'Informe o local de saída.';
        } elseif (mb_strlen($localSaida) > 180) {
            $erros['local_saida'] = 'O local de saída deve ter no máximo 180 caracteres.';
        } elseif ($localSaida !== '' && !($origemLatitudeInformada && $origemLongitudeInformada) && !self::enderecoPareceCompleto($localSaida)) {
            $erros['local_saida'] = 'Informe o ponto de partida com endereço completo: rua/local, número ou referência, bairro, cidade e UF.';
        }

        if ($origemLatitudeInformada xor $origemLongitudeInformada) {
            $erros['local_saida_coordenadas'] = 'Informe latitude e longitude do ponto de partida juntas.';
        }

        if ($origemLatitudeInformada && $origemLongitudeInformada) {
            if (!is_numeric($dados['local_saida_latitude']) || !is_numeric($dados['local_saida_longitude'])) {
                $erros['local_saida_coordenadas'] = 'Latitude e longitude do ponto de partida devem ser numéricas.';
            }
        }

        if (empty($dados['transporte']) || !in_array($dados['transporte'], self::TRANSPORTES_VALIDOS, true)) {
            $erros['transporte'] = 'Informe um transporte válido: carro, moto ou a_pe.';
        }

        if (isset($dados['raio_cluster_km'])) {
            if (!is_numeric($dados['raio_cluster_km'])) {
                $erros['raio_cluster_km'] = 'O raio do cluster deve ser numérico.';
            } elseif ((float) $dados['raio_cluster_km'] < 2 || (float) $dados['raio_cluster_km'] > 5) {
                $erros['raio_cluster_km'] = 'O raio do cluster deve ficar entre 2 km e 5 km.';
            }
        }

        if (!isset($dados['visitas']) || !is_array($dados['visitas']) || $dados['visitas'] === []) {
            $erros['visitas'] = 'Informe pelo menos 2 compromissos presenciais para otimizar a sequência do dia.';
            return $erros;
        }

        if (count($dados['visitas']) < self::MINIMO_COMPROMISSOS_OTIMIZACAO) {
            $erros['visitas'] = 'Informe pelo menos 2 compromissos presenciais para otimizar a sequência do dia.';
        }

        if (count($dados['visitas']) > 100) {
            $erros['visitas'] = 'O roteiro suporta no máximo 100 visitas por processamento.';
            return $erros;
        }

        foreach ($dados['visitas'] as $indice => $visita) {
            if (!is_array($visita)) {
                $erros["visitas.{$indice}"] = 'Cada visita deve ser um objeto válido.';
                continue;
            }

            self::validarVisita($visita, $indice, $erros);
        }

        return $erros;
    }

    private static function validarVisita(array $visita, int $indice, array &$erros): void
    {
        $nome = trim((string) ($visita['nome'] ?? ''));
        if ($nome === '' && empty($visita['agenda_evento_id']) && empty($visita['apoiador_id'])) {
            $erros["visitas.{$indice}.nome"] = 'Informe o nome da visita ou vincule agenda/apoiador.';
        }

        if ($nome !== '' && mb_strlen($nome) > 160) {
            $erros["visitas.{$indice}.nome"] = 'O nome da visita deve ter no máximo 160 caracteres.';
        }

        $endereco = trim((string) ($visita['endereco'] ?? ''));
        $latitudeInformada = array_key_exists('latitude', $visita);
        $longitudeInformada = array_key_exists('longitude', $visita);

        if ($endereco === '' && empty($visita['agenda_evento_id']) && !($latitudeInformada && $longitudeInformada)) {
            $erros["visitas.{$indice}.endereco"] = 'Informe o endereço da visita.';
        }

        if ($endereco !== '' && mb_strlen($endereco) > 255) {
            $erros["visitas.{$indice}.endereco"] = 'O endereço da visita deve ter no máximo 255 caracteres.';
        } elseif ($endereco !== '' && !($latitudeInformada && $longitudeInformada) && !self::enderecoPareceCompleto($endereco)) {
            $erros["visitas.{$indice}.endereco"] = 'Informe o endereço completo do compromisso: rua/local, número ou referência, bairro, cidade e UF.';
        }

        if ($latitudeInformada xor $longitudeInformada) {
            $erros["visitas.{$indice}.coordenadas"] = 'Informe latitude e longitude juntas.';
        }

        if ($latitudeInformada && $longitudeInformada) {
            if (!is_numeric($visita['latitude']) || !is_numeric($visita['longitude'])) {
                $erros["visitas.{$indice}.coordenadas"] = 'Latitude e longitude devem ser numéricas.';
            }
        }

        if (isset($visita['prioridade']) && !in_array($visita['prioridade'], self::PRIORIDADES_VALIDAS, true)) {
            $erros["visitas.{$indice}.prioridade"] = 'Prioridade inválida. Use alta, media ou baixa.';
        }

        if (!empty($visita['horario_inicio']) && !self::dataHoraValida((string) $visita['horario_inicio'])) {
            $erros["visitas.{$indice}.horario_inicio"] = 'O horário de início é inválido.';
        }

        if (!empty($visita['horario_fim']) && !self::dataHoraValida((string) $visita['horario_fim'])) {
            $erros["visitas.{$indice}.horario_fim"] = 'O horário de fim é inválido.';
        }

        if (
            !empty($visita['horario_inicio'])
            && !empty($visita['horario_fim'])
            && strtotime((string) $visita['horario_fim']) < strtotime((string) $visita['horario_inicio'])
        ) {
            $erros["visitas.{$indice}.horario_fim"] = 'O horário de fim deve ser maior ou igual ao horário de início.';
        }
    }

    private static function dataValida(string $valor): bool
    {
        $data = \DateTimeImmutable::createFromFormat('Y-m-d', $valor);
        return $data !== false && $data->format('Y-m-d') === $valor;
    }

    private static function dataHoraValida(string $valor): bool
    {
        try {
            new \DateTimeImmutable($valor);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function enderecoPareceCompleto(string $valor): bool
    {
        $partes = array_values(array_filter(array_map(
            static fn (string $parte): string => trim($parte),
            explode(',', $valor)
        )));

        if (count($partes) < 4) {
            return false;
        }

        $uf = mb_strtoupper((string) end($partes));
        return preg_match('/^[A-Z]{2}$/', $uf) === 1;
    }
}