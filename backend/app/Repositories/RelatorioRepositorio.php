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
}
