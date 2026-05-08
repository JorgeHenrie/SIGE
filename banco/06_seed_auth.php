<?php
/**
 * SIGE - Seeder de Autenticação via CPF
 * Uso: C:\php83\php.exe banco/06_seed_auth.php
 *
 * Gera os hashes corretos e insere/atualiza o usuário admin
 * com CPF para login.
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../backend');
$dotenv->load();

// -------------------------------------------------------
// Configuração
// -------------------------------------------------------
$cpf      = '02825733210';   // CPF a cadastrar
$senha    = 'W34@jy1c';      // Senha em texto puro

// -------------------------------------------------------
// Geração de hashes
// -------------------------------------------------------
$hmacKey    = $_ENV['APP_CPF_HMAC_KEY'];
$encryptKey = $_ENV['APP_CPF_ENCRYPT_KEY'];

// Hash HMAC-SHA256 do CPF (igual ao CpfAuxiliar::gerarHash)
$cpfHash = hash_hmac('sha256', $cpf, $hmacKey);

// CPF criptografado AES-256-CBC (igual ao CpfAuxiliar::criptografar)
$chaveAes = hash('sha256', $encryptKey, true);
$iv       = random_bytes(16);
$cifrado  = openssl_encrypt($cpf, 'AES-256-CBC', $chaveAes, OPENSSL_RAW_DATA, $iv);
$cpfCifrado = base64_encode($iv . $cifrado);

// Hash bcrypt da senha (custo 12, compatível com password_verify)
$senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

echo "CPF hash   : {$cpfHash}\n";
echo "Senha hash : {$senhaHash}\n";

// -------------------------------------------------------
// Conexão PDO
// -------------------------------------------------------
$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s;options=--search_path=%s',
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT'],
    $_ENV['DB_NOME'],
    $_ENV['DB_SCHEMA']
);
$pdo = new PDO($dsn, $_ENV['DB_USUARIO'], $_ENV['DB_SENHA'], [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);

// -------------------------------------------------------
// Upsert: atualiza o admin existente com CPF + nova senha
// -------------------------------------------------------
$sql = "
    UPDATE sige.usuarios
    SET cpf      = :cpf,
        cpf_hash = :cpf_hash,
        senha    = :senha
    WHERE email = 'admin@sige.local'
    RETURNING id, nome, email
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':cpf'      => $cpfCifrado,
    ':cpf_hash' => $cpfHash,
    ':senha'    => $senhaHash,
]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo "Usuário atualizado: {$row['nome']} ({$row['email']}) — ID: {$row['id']}\n";
    echo "Login disponível com CPF: {$cpf} / Senha: {$senha}\n";
} else {
    echo "AVISO: nenhum registro atualizado. Verifique se admin@sige.local existe.\n";
}
