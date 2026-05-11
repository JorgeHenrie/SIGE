<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Excecoes\AutorizacaoException;
use App\Core\Excecoes\NaoEncontradoException;
use App\Core\Excecoes\ValidacaoException;
use App\Repositories\CombustivelRepositorio;
use App\Repositories\LiderRepositorio;
use App\Validators\CombustivelValidador;

class CombustivelServico
{
    private CombustivelRepositorio $repositorio;
    private LiderRepositorio $liderRepositorio;

    public function __construct()
    {
        $this->repositorio = new CombustivelRepositorio();
        $this->liderRepositorio = new LiderRepositorio();
    }

    public function listar(int $pagina, int $limite, string $busca, array $auth): array
    {
        $pagina = max(1, $pagina);
        $limite = min(100, max(1, $limite));

        $liderId = $this->perfilEhLider($auth) ? (string) ($auth['sub'] ?? '') : null;

        return $this->repositorio->listar($pagina, $limite, $busca, $liderId);
    }

    public function buscarPorId(string $id, array $auth): array
    {
        $registro = $this->repositorio->buscarPorId($id);

        if (!$registro) {
            throw new NaoEncontradoException('Abastecimento');
        }

        $this->garantirAcessoAoRegistro($registro, $auth);

        return $registro;
    }

    public function cadastrar(array $dados, array $auth): array
    {
        if (!$this->podeLancarCombustivel($auth)) {
            throw new AutorizacaoException('Apenas líderes, gestores e administradores podem lançar abastecimentos.');
        }

        $dados['lider_id'] = $this->resolverLiderId($dados, $auth);

        $erros = CombustivelValidador::validarCadastro($dados);

        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        if (!$this->liderRepositorio->buscarPorId($dados['lider_id'])) {
            throw new NaoEncontradoException('Líder');
        }

        $payload = [
            'lider_id' => $dados['lider_id'],
            'criado_por_usuario_id' => $this->perfilEhLider($auth) ? null : ($auth['sub'] ?? null),
            'veiculo_descricao' => $this->normalizarTextoObrigatorio((string) $dados['veiculo_descricao']),
            'placa_veiculo' => $this->normalizarPlaca((string) $dados['placa_veiculo']),
            'tipo_combustivel' => $this->normalizarTipoCombustivel((string) $dados['tipo_combustivel']),
            'motorista_nome' => $this->normalizarTextoObrigatorio((string) $dados['motorista_nome']),
            'local_abastecimento' => $this->normalizarTextoObrigatorio((string) $dados['local_abastecimento']),
            'odometro_atual' => $this->normalizarOdometro($dados['odometro_atual']),
            'litros_abastecidos' => $this->normalizarValor($dados['litros_abastecidos']),
            'valor_total' => $this->normalizarValor($dados['valor_total']),
            'finalidade' => $this->normalizarTextoObrigatorio((string) $dados['finalidade']),
            'numero_nota_fiscal' => $this->normalizarTextoObrigatorio((string) $dados['numero_nota_fiscal']),
            'data_abastecimento' => $this->normalizarDataHora((string) $dados['data_abastecimento']),
            'observacoes' => $this->normalizarTexto($dados['observacoes'] ?? null),
        ];

        return $this->repositorio->criar(array_merge($payload, $this->processarFotoNotaFiscal($dados)));
    }

    public function atualizar(string $id, array $dados, array $auth): array
    {
        $registro = $this->buscarPorId($id, $auth);

        if ($this->perfilEhLider($auth) && $registro['lider_id'] !== ($auth['sub'] ?? null)) {
            throw new AutorizacaoException('Você não pode editar lançamentos de outro líder.');
        }

        if (isset($dados['lider_id']) && !$this->podeGerenciarTodos($auth)) {
            unset($dados['lider_id']);
        }

        $erros = CombustivelValidador::validarAtualizacao($dados);

        if (!empty($erros)) {
            throw new ValidacaoException($erros);
        }

        if (!empty($dados['lider_id']) && !$this->liderRepositorio->buscarPorId((string) $dados['lider_id'])) {
            throw new NaoEncontradoException('Líder');
        }

        $payload = [];

        if (array_key_exists('lider_id', $dados)) {
            $payload['lider_id'] = $dados['lider_id'];
        }

        if (array_key_exists('veiculo_descricao', $dados)) {
            $payload['veiculo_descricao'] = $this->normalizarTextoObrigatorio((string) $dados['veiculo_descricao']);
        }

        if (array_key_exists('placa_veiculo', $dados)) {
            $payload['placa_veiculo'] = $this->normalizarPlaca((string) $dados['placa_veiculo']);
        }

        if (array_key_exists('tipo_combustivel', $dados)) {
            $payload['tipo_combustivel'] = $this->normalizarTipoCombustivel((string) $dados['tipo_combustivel']);
        }

        if (array_key_exists('motorista_nome', $dados)) {
            $payload['motorista_nome'] = $this->normalizarTextoObrigatorio((string) $dados['motorista_nome']);
        }

        if (array_key_exists('local_abastecimento', $dados)) {
            $payload['local_abastecimento'] = $this->normalizarTextoObrigatorio((string) $dados['local_abastecimento']);
        }

        if (array_key_exists('odometro_atual', $dados)) {
            $payload['odometro_atual'] = $this->normalizarOdometro($dados['odometro_atual']);
        }

        if (array_key_exists('litros_abastecidos', $dados)) {
            $payload['litros_abastecidos'] = $this->normalizarValor($dados['litros_abastecidos']);
        }

        if (array_key_exists('valor_total', $dados)) {
            $payload['valor_total'] = $this->normalizarValor($dados['valor_total']);
        }

        if (array_key_exists('finalidade', $dados)) {
            $payload['finalidade'] = $this->normalizarTextoObrigatorio((string) $dados['finalidade']);
        }

        if (array_key_exists('numero_nota_fiscal', $dados)) {
            $payload['numero_nota_fiscal'] = $this->normalizarTextoObrigatorio((string) $dados['numero_nota_fiscal']);
        }

        if (array_key_exists('data_abastecimento', $dados)) {
            $payload['data_abastecimento'] = $this->normalizarDataHora((string) $dados['data_abastecimento']);
        }

        if (array_key_exists('observacoes', $dados)) {
            $payload['observacoes'] = $this->normalizarTexto($dados['observacoes']);
        }

        $payload = array_merge($payload, $this->processarFotoNotaFiscal($dados, $registro));

        $resultado = $this->repositorio->atualizar($id, $payload);

        if (!$resultado) {
            throw new NaoEncontradoException('Abastecimento');
        }

        return $resultado;
    }

    public function remover(string $id, array $auth): void
    {
        $registro = $this->buscarPorId($id, $auth);

        if ($this->perfilEhLider($auth) && $registro['lider_id'] !== ($auth['sub'] ?? null)) {
            throw new AutorizacaoException('Você não pode remover lançamentos de outro líder.');
        }

        if (!$this->perfilEhLider($auth) && !$this->podeGerenciarTodos($auth)) {
            throw new AutorizacaoException('Acesso negado para remover este abastecimento.');
        }

        if (!$this->repositorio->remover($id)) {
            throw new NaoEncontradoException('Abastecimento');
        }
    }

    private function garantirAcessoAoRegistro(array $registro, array $auth): void
    {
        if ($this->perfilEhLider($auth) && $registro['lider_id'] !== ($auth['sub'] ?? null)) {
            throw new AutorizacaoException('Você não pode acessar abastecimentos de outro líder.');
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

    private function podeGerenciarTodos(array $auth): bool
    {
        return in_array($auth['perfil'] ?? '', ['admin', 'gestor'], true);
    }

    private function podeLancarCombustivel(array $auth): bool
    {
        return $this->perfilEhLider($auth) || $this->podeGerenciarTodos($auth);
    }

    private function normalizarPlaca(string $valor): string
    {
        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper(trim($valor)));
    }

    private function normalizarValor(mixed $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }

    private function normalizarOdometro(mixed $valor): int
    {
        return (int) round((float) $valor);
    }

    private function normalizarTipoCombustivel(string $valor): string
    {
        return mb_strtolower(trim($valor));
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

    private function normalizarTextoObrigatorio(string $valor): string
    {
        return trim($valor);
    }

    private function processarFotoNotaFiscal(array $dados, ?array $registroAtual = null): array
    {
        $removerFoto = filter_var($dados['remover_foto_nota_fiscal'] ?? false, FILTER_VALIDATE_BOOL);
        $fotoBase64 = trim((string) ($dados['nota_fiscal_foto_base64'] ?? ''));

        if ($fotoBase64 === '' && !$removerFoto) {
            return [];
        }

        if ($fotoBase64 !== '') {
            $foto = $this->salvarFotoNotaFiscal(
                $fotoBase64,
                (string) ($dados['nota_fiscal_foto_nome'] ?? 'nota-fiscal')
            );

            if (!empty($registroAtual['foto_nota_fiscal_caminho'])) {
                $this->excluirFotoNotaFiscal((string) $registroAtual['foto_nota_fiscal_caminho']);
            }

            return $foto;
        }

        if (!empty($registroAtual['foto_nota_fiscal_caminho'])) {
            $this->excluirFotoNotaFiscal((string) $registroAtual['foto_nota_fiscal_caminho']);
        }

        return [
            'foto_nota_fiscal_caminho' => null,
            'foto_nota_fiscal_nome' => null,
            'foto_nota_fiscal_mime' => null,
        ];
    }

    private function salvarFotoNotaFiscal(string $fotoBase64, string $nomeOriginal): array
    {
        if (!preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $fotoBase64, $matches)) {
            throw new ValidacaoException(['Envie uma imagem valida para a foto da nota fiscal.']);
        }

        $conteudo = base64_decode($matches[2], true);

        if ($conteudo === false) {
            throw new ValidacaoException(['Nao foi possivel ler a foto da nota fiscal enviada.']);
        }

        if (strlen($conteudo) > 5 * 1024 * 1024) {
            throw new ValidacaoException(['A foto da nota fiscal deve ter no maximo 5 MB.']);
        }

        $extensoesPermitidas = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        $mimeDeclarado = mb_strtolower((string) $matches[1]);

        if (!isset($extensoesPermitidas[$mimeDeclarado]) || !$this->assinaturaImagemConfere($conteudo, $mimeDeclarado)) {
            throw new ValidacaoException(['A foto da nota fiscal deve estar em JPG, PNG ou WEBP valido.']);
        }

        $diretorioFisico = $this->diretorioUploadsNotaFiscal();

        if (!is_dir($diretorioFisico) && !mkdir($diretorioFisico, 0775, true) && !is_dir($diretorioFisico)) {
            throw new \RuntimeException('Nao foi possivel preparar o armazenamento da foto da nota fiscal.');
        }

        $nomeArquivo = sprintf('%s.%s', bin2hex(random_bytes(16)), $extensoesPermitidas[$mimeDeclarado]);
        $caminhoFisico = $diretorioFisico . DIRECTORY_SEPARATOR . $nomeArquivo;

        if (file_put_contents($caminhoFisico, $conteudo) === false) {
            throw new \RuntimeException('Nao foi possivel salvar a foto da nota fiscal.');
        }

        return [
            'foto_nota_fiscal_caminho' => '/uploads/combustivel-notas/' . $nomeArquivo,
            'foto_nota_fiscal_nome' => $this->normalizarNomeArquivoOriginal($nomeOriginal),
            'foto_nota_fiscal_mime' => $mimeDeclarado,
        ];
    }

    private function assinaturaImagemConfere(string $conteudo, string $mime): bool
    {
        return match ($mime) {
            'image/png' => str_starts_with($conteudo, "\x89PNG\r\n\x1a\n"),
            'image/jpeg' => str_starts_with($conteudo, "\xFF\xD8\xFF"),
            'image/webp' => str_starts_with($conteudo, 'RIFF') && substr($conteudo, 8, 4) === 'WEBP',
            default => false,
        };
    }

    private function excluirFotoNotaFiscal(string $caminhoRelativo): void
    {
        $caminhoFisico = $this->resolverCaminhoFisicoFoto($caminhoRelativo);

        if ($caminhoFisico !== null && is_file($caminhoFisico)) {
            @unlink($caminhoFisico);
        }
    }

    private function diretorioUploadsNotaFiscal(): string
    {
        return $this->basePath() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'combustivel-notas';
    }

    private function resolverCaminhoFisicoFoto(string $caminhoRelativo): ?string
    {
        if ($caminhoRelativo === '' || !str_starts_with($caminhoRelativo, '/uploads/combustivel-notas/')) {
            return null;
        }

        return $this->basePath() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($caminhoRelativo, '/'));
    }

    private function normalizarNomeArquivoOriginal(string $nomeOriginal): string
    {
        $nome = preg_replace('/[^A-Za-z0-9._ -]/', '-', trim($nomeOriginal));
        $nome = trim((string) $nome, '. ');

        if ($nome === '') {
            return 'nota-fiscal';
        }

        return mb_substr($nome, 0, 160);
    }

    private function basePath(): string
    {
        return defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
    }
}