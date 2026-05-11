<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\AgendaController;
use App\Controllers\ApoiadorController;
use App\Controllers\CombustivelController;
use App\Controllers\LiderController;
use App\Controllers\RoteiroController;
use App\Controllers\RelatorioController;

// ============================================================
// ROTAS — AUTENTICAÇÃO (pública)
// ============================================================
$roteador->post('/api/auth/login', [AuthController::class, 'login']);

// ============================================================
// ROTAS — LÍDERES
// ============================================================
$roteador->get('/api/lideres',                    [LiderController::class, 'listar']);
$roteador->get('/api/lideres/{id}',               [LiderController::class, 'visualizar']);
$roteador->post('/api/lideres',                   [LiderController::class, 'cadastrar']);
$roteador->put('/api/lideres/{id}',               [LiderController::class, 'atualizar']);
$roteador->delete('/api/lideres/{id}',            [LiderController::class, 'remover']);

// ============================================================
// ROTAS — APOIADORES
// Rota específica /lideres/{id}/apoiadores registrada antes
// de /apoiadores/{id} para evitar colisão de padrões.
// ============================================================
$roteador->get('/api/lideres/{id}/apoiadores',    [ApoiadorController::class, 'listarPorLider']);
$roteador->get('/api/apoiadores',                 [ApoiadorController::class, 'listar']);
$roteador->get('/api/apoiadores/{id}',            [ApoiadorController::class, 'visualizar']);
$roteador->post('/api/apoiadores',                [ApoiadorController::class, 'cadastrar']);
$roteador->put('/api/apoiadores/{id}',            [ApoiadorController::class, 'atualizar']);
$roteador->delete('/api/apoiadores/{id}',         [ApoiadorController::class, 'remover']);

// ============================================================
// ROTAS — RELATÓRIOS
// ============================================================
$roteador->get('/api/relatorios/resumo',          [RelatorioController::class, 'resumo']);
$roteador->get('/api/relatorios/por-lider',       [RelatorioController::class, 'porLider']);
$roteador->get('/api/relatorios/por-bairro',      [RelatorioController::class, 'porBairro']);
$roteador->get('/api/relatorios/consolidado',     [RelatorioController::class, 'consolidado']);
$roteador->get('/api/relatorios/combustivel-semanal',   [RelatorioController::class, 'combustivelSemanal']);
$roteador->get('/api/relatorios/combustivel-mensal',    [RelatorioController::class, 'combustivelMensal']);
$roteador->get('/api/relatorios/combustivel-por-lider', [RelatorioController::class, 'combustivelPorLider']);
$roteador->get('/api/relatorios/combustivel-alertas',   [RelatorioController::class, 'combustivelAlertas']);

// ============================================================
// ROTAS — COMBUSTÍVEL
// ============================================================
$roteador->get('/api/combustivel',                [CombustivelController::class, 'listar']);
$roteador->post('/api/combustivel',               [CombustivelController::class, 'cadastrar']);
$roteador->get('/api/combustivel/{id}',           [CombustivelController::class, 'visualizar']);
$roteador->put('/api/combustivel/{id}',           [CombustivelController::class, 'atualizar']);
$roteador->delete('/api/combustivel/{id}',        [CombustivelController::class, 'remover']);

// ============================================================
// ROTAS — ROTEIRIZAÇÃO INTELIGENTE
// ============================================================
$roteador->post('/api/roteiros/sugerir',          [RoteiroController::class, 'sugerir']);
$roteador->get('/api/roteiros',                   [RoteiroController::class, 'listar']);
$roteador->post('/api/roteiros',                  [RoteiroController::class, 'cadastrar']);
$roteador->get('/api/roteiros/{id}',              [RoteiroController::class, 'visualizar']);
$roteador->put('/api/roteiros/{id}/recalcular',   [RoteiroController::class, 'recalcular']);
$roteador->delete('/api/roteiros/{id}',           [RoteiroController::class, 'remover']);

// ============================================================
// ROTAS — AGENDA
// ============================================================
$roteador->get('/api/agenda',                     [AgendaController::class, 'listar']);
$roteador->post('/api/agenda',                    [AgendaController::class, 'cadastrar']);
$roteador->post('/api/agenda/{id}/aprovar',       [AgendaController::class, 'aprovar']);
$roteador->post('/api/agenda/{id}/recusar',       [AgendaController::class, 'recusar']);
$roteador->get('/api/agenda/{id}',                [AgendaController::class, 'visualizar']);
$roteador->put('/api/agenda/{id}',                [AgendaController::class, 'atualizar']);
$roteador->delete('/api/agenda/{id}',             [AgendaController::class, 'remover']);
