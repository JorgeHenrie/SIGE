<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Excecoes\AutorizacaoException;
use App\Core\Excecoes\NaoEncontradoException;
use App\Core\Excecoes\ValidacaoException;
use App\Repositories\AgendaRepositorio;
use App\Repositories\LiderRepositorio;
use App\Validators\AgendaValidador;

class AgendaServico
{
    private AgendaRepositorio $repositorio;
    private LiderRepositorio $liderRepositorio;

    public function __construct()
    {
        $this->repositorio = new AgendaRepositorio();
        $this->liderRepositorio = new LiderRepositorio();
    }

    public function listar(int $pagina, int $limite, string $busca, ?string $status, array $auth): array
    {
        $pagina = max(1, $pagina);
        $limite = min(100, max(1, $limite));

        $liderId = $this->perfilEhLider($auth) ? (string) ($auth['sub'] ?? '') : null;

        return $this->repositorio->listar($pagina, $limite, $busca, $status, $liderId);
    }

    public function buscarPorId(string $id, array $auth): array
    {
        $evento = $this->repositorio->buscarPorId($id);

        if (!$evento) {
            throw new NaoEncontradoException('Evento de agenda');
        }

        $this->garantirAcessoAoEvento($evento, $auth);

        return $evento;
    }

    public function cadastrar(array $dados, array $auth): array
    {
        if (!$this->podeCriarSolicitacao($auth)) {
            throw new AutorizacaoException('Apenas líderes, gestores e administradores podem abrir solicitações de agenda.');
        }

        $dados['lider_id'] = $this->resolverLiderId($dados, $auth);

        $erros = AgendaValidador::validarCadastro($dados);

        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        if (!$this->liderRepositorio->buscarPorId($dados['lider_id'])) {
            throw new NaoEncontradoException('Líder');
        }

        $payload = [
            'lider_id' => $dados['lider_id'],
            'criado_por_usuario_id' => $this->perfilEhLider($auth) ? null : ($auth['sub'] ?? null),
            'titulo' => trim((string) $dados['titulo']),
            'tipo' => $dados['tipo'] ?? 'reuniao',
            'descricao' => $this->normalizarTexto($dados['descricao'] ?? null),
            'local_evento' => $this->normalizarTexto($dados['local_evento'] ?? null),
            'data_solicitada_inicio' => $this->normalizarDataHora((string) $dados['data_solicitada_inicio']),
            'data_solicitada_fim' => $this->normalizarDataHora($dados['data_solicitada_fim'] ?? null),
            'status' => 'pendente',
            'observacoes_solicitacao' => $this->normalizarTexto($dados['observacoes_solicitacao'] ?? null),
        ];

        return $this->repositorio->criar($payload);
    }

    public function atualizar(string $id, array $dados, array $auth): array
    {
        $evento = $this->buscarPorId($id, $auth);

        if ($evento['status'] !== 'pendente') {
            throw new ValidacaoException(['Somente solicitações pendentes podem ser alteradas.']);
        }

        if ($this->perfilEhLider($auth) && $evento['lider_id'] !== ($auth['sub'] ?? null)) {
            throw new AutorizacaoException('Você não pode editar solicitações de outro líder.');
        }

        if (isset($dados['lider_id']) && !$this->podeDecidirAgenda($auth)) {
            unset($dados['lider_id']);
        }

        $erros = AgendaValidador::validarAtualizacao($dados);

        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        if (!empty($dados['lider_id']) && !$this->liderRepositorio->buscarPorId((string) $dados['lider_id'])) {
            throw new NaoEncontradoException('Líder');
        }

        $payload = [];
        foreach (['lider_id', 'titulo', 'tipo'] as $campo) {
            if (array_key_exists($campo, $dados)) {
                $payload[$campo] = $dados[$campo];
            }
        }

        if (array_key_exists('descricao', $dados)) {
            $payload['descricao'] = $this->normalizarTexto($dados['descricao']);
        }

        if (array_key_exists('local_evento', $dados)) {
            $payload['local_evento'] = $this->normalizarTexto($dados['local_evento']);
        }

        if (array_key_exists('observacoes_solicitacao', $dados)) {
            $payload['observacoes_solicitacao'] = $this->normalizarTexto($dados['observacoes_solicitacao']);
        }

        if (array_key_exists('data_solicitada_inicio', $dados)) {
            $payload['data_solicitada_inicio'] = $this->normalizarDataHora((string) $dados['data_solicitada_inicio']);
        }

        if (array_key_exists('data_solicitada_fim', $dados)) {
            $payload['data_solicitada_fim'] = $this->normalizarDataHora($dados['data_solicitada_fim']);
        }

        $resultado = $this->repositorio->atualizar($id, $payload);

        if (!$resultado) {
            throw new NaoEncontradoException('Evento de agenda');
        }

        return $resultado;
    }

    public function aprovar(string $id, array $dados, array $auth): array
    {
        if (!$this->podeDecidirAgenda($auth)) {
            throw new AutorizacaoException('Apenas gestor ou administrador podem aprovar solicitações de agenda.');
        }

        $evento = $this->buscarPorId($id, $auth);

        if ($evento['status'] !== 'pendente') {
            throw new ValidacaoException(['Apenas solicitações pendentes podem ser aprovadas.']);
        }

        $dados['data_confirmada_inicio'] = $dados['data_confirmada_inicio'] ?? $evento['data_solicitada_inicio'];
        $dados['data_confirmada_fim'] = array_key_exists('data_confirmada_fim', $dados)
            ? $dados['data_confirmada_fim']
            : ($evento['data_solicitada_fim'] ?? null);

        $erros = AgendaValidador::validarAprovacao($dados);

        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        return $this->repositorio->atualizar($id, [
            'status' => 'aprovado',
            'data_confirmada_inicio' => $this->normalizarDataHora((string) $dados['data_confirmada_inicio']),
            'data_confirmada_fim' => $this->normalizarDataHora($dados['data_confirmada_fim'] ?? null),
            'observacoes_decisao' => $this->normalizarTexto($dados['observacoes_decisao'] ?? null),
            'decidido_por' => $auth['sub'] ?? null,
            'decidido_em' => date('Y-m-d H:i:s'),
        ]);
    }

    public function recusar(string $id, array $dados, array $auth): array
    {
        if (!$this->podeDecidirAgenda($auth)) {
            throw new AutorizacaoException('Apenas gestor ou administrador podem recusar solicitações de agenda.');
        }

        $evento = $this->buscarPorId($id, $auth);

        if ($evento['status'] !== 'pendente') {
            throw new ValidacaoException(['Apenas solicitações pendentes podem ser recusadas.']);
        }

        $erros = AgendaValidador::validarRecusa($dados);

        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        return $this->repositorio->atualizar($id, [
            'status' => 'recusado',
            'data_confirmada_inicio' => null,
            'data_confirmada_fim' => null,
            'observacoes_decisao' => $this->normalizarTexto($dados['observacoes_decisao'] ?? null),
            'decidido_por' => $auth['sub'] ?? null,
            'decidido_em' => date('Y-m-d H:i:s'),
        ]);
    }

    public function remover(string $id, array $auth): void
    {
        $evento = $this->buscarPorId($id, $auth);

        if ($evento['status'] !== 'pendente') {
            throw new ValidacaoException(['Somente solicitações pendentes podem ser removidas.']);
        }

        if ($this->perfilEhLider($auth) && $evento['lider_id'] !== ($auth['sub'] ?? null)) {
            throw new AutorizacaoException('Você não pode remover solicitações de outro líder.');
        }

        if (!$this->perfilEhLider($auth) && !$this->podeDecidirAgenda($auth)) {
            throw new AutorizacaoException('Acesso negado para remover esta solicitação.');
        }

        if (!$this->repositorio->remover($id)) {
            throw new NaoEncontradoException('Evento de agenda');
        }
    }

    private function garantirAcessoAoEvento(array $evento, array $auth): void
    {
        if ($this->perfilEhLider($auth) && $evento['lider_id'] !== ($auth['sub'] ?? null)) {
            throw new AutorizacaoException('Você não pode acessar eventos de agenda de outro líder.');
        }
    }

    private function resolverLiderId(array $dados, array $auth): string
    {
        if ($this->perfilEhLider($auth)) {
            return (string) ($auth['sub'] ?? '');
        }

        return (string) ($dados['lider_id'] ?? '');
    }

    private function perfilEhLider(array $auth): bool
    {
        return ($auth['perfil'] ?? null) === 'lider' && ($auth['tipo'] ?? null) === 'lider';
    }

    private function podeDecidirAgenda(array $auth): bool
    {
        return in_array($auth['perfil'] ?? '', ['admin', 'gestor'], true);
    }

    private function podeCriarSolicitacao(array $auth): bool
    {
        return $this->perfilEhLider($auth) || $this->podeDecidirAgenda($auth);
    }

    private function normalizarDataHora(?string $valor): ?string
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }

        return (new \DateTimeImmutable($valor))->format('Y-m-d H:i:s');
    }

    private function normalizarTexto(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim($valor);
        return $texto === '' ? null : $texto;
    }
}