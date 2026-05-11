<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Banco;
use PDO;

class RelatorioRepositorio
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Banco::conexao();
    }

    public function resumo(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM sige.vw_relatorio_resumo");
        return $stmt->fetch() ?: [];
    }

    public function porLider(): array
    {
        $stmt = $this->pdo->query("
            SELECT * FROM sige.vw_relatorio_por_lider
            ORDER BY ranking_votos ASC
        ");
        return $stmt->fetchAll();
    }

    public function porBairro(): array
    {
        $stmt = $this->pdo->query("
            SELECT * FROM sige.vw_relatorio_por_bairro
            ORDER BY bairro ASC, tipo ASC
        ");
        return $stmt->fetchAll();
    }

    public function consolidado(): array
    {
        $stmt = $this->pdo->query("
            SELECT * FROM sige.vw_relatorio_consolidado
            ORDER BY posicao_ranking ASC
        ");
        return $stmt->fetchAll();
    }

    public function combustivelSemanal(): array
    {
        $stmt = $this->pdo->query("
            SELECT * FROM sige.vw_relatorio_combustivel_semanal
            ORDER BY semana_referencia DESC
        ");
        return $stmt->fetchAll();
    }

    public function combustivelMensal(): array
    {
        $stmt = $this->pdo->query("
            SELECT * FROM sige.vw_relatorio_combustivel_mensal
            ORDER BY mes_referencia DESC
        ");
        return $stmt->fetchAll();
    }

    public function combustivelPorLider(): array
    {
        $stmt = $this->pdo->query("
            SELECT * FROM sige.vw_relatorio_combustivel_por_lider
            ORDER BY total_gasto DESC, total_abastecimentos DESC, lider_nome ASC
        ");
        return $stmt->fetchAll();
    }
    
    public function combustivelAlertas(): array
    {
        $stmt = $this->pdo->query("
            SELECT * FROM sige.vw_relatorio_combustivel_alertas
            ORDER BY
                CASE alerta_nivel
                    WHEN 'alto' THEN 1
                    WHEN 'medio' THEN 2
                    ELSE 3
                END,
                data_abastecimento DESC,
                placa_veiculo ASC
        ");
        return $stmt->fetchAll();
    }
}
