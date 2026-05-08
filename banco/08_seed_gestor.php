<?php
/**
 * SIGE - Seeder do perfil Gestor
 * Uso: C:\php83\php.exe banco/08_seed_gestor.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../backend/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../backend');
$dotenv->load();

$nome  = 'Gestor de Agenda';
$email = 'gestor@sige.local';
$perfil = 'gestor';
$cpf   = '52998224725';
$senha = 'Gestor@2026';

$hmacKey    = $_ENV['APP_CPF_HMAC_KEY'];
$encryptKey = $_ENV['APP_CPF_ENCRYPT_KEY'];

$cpfHash = hash_hmac('sha256', $cpf, $hmacKey);

$chaveAes = hash('sha256', $encryptKey, true);
$iv       = random_bytes(16);
$cifrado  = openssl_encrypt($cpf, 'AES-256-CBC', $chaveAes, OPENSSL_RAW_DATA, $iv);
$cpfCifrado = base64_encode($iv . $cifrado);

$senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s;options=--search_path=%s',
    $_ENV['DB_HOST'],
    $_ENV['DB_PORT'],
    $_ENV['DB_NOME'],
    $_ENV['DB_SCHEMA']
);

$pdo = new PDO($dsn, $_ENV['DB_USUARIO'], $_ENV['DB_SENHA'], [
    PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$sql = "
    INSERT INTO sige.usuarios (nome, email, senha, perfil, status, cpf, cpf_hash)
    VALUES (:nome, :email, :senha, :perfil, TRUE, :cpf, :cpf_hash)
    ON CONFLICT (email) DO UPDATE SET
        nome = EXCLUDED.nome,
        senha = EXCLUDED.senha,
        perfil = EXCLUDED.perfil,
        status = TRUE,
        cpf = EXCLUDED.cpf,
        cpf_hash = EXCLUDED.cpf_hash,
        excluido_em = NULL
    RETURNING id, nome, email, perfil
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':nome'     => $nome,
    ':email'    => $email,
    ':senha'    => $senhaHash,
    ':perfil'   => $perfil,
    ':cpf'      => $cpfCifrado,
    ':cpf_hash' => $cpfHash,
]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Gestor configurado: {$row['nome']} ({$row['email']}) - Perfil: {$row['perfil']}\n";
echo "Login disponível com CPF: {$cpf} / Senha: {$senha}\n";