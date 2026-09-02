<?php

declare(strict_types=1);

/**
 * Instalación one-shot + seed opcional de BookNest.json
 */

require_once __DIR__ . '/config/env.php';

header('Content-Type: text/html; charset=utf-8');

$host = bn_env('DB_HOST', '127.0.0.1');
$port = (int) bn_env('DB_PORT', '3306');
$name = bn_env('DB_NAME', 'booknest');
$user = bn_env('DB_USER', 'root');
$pass = bn_env('DB_PASS', '') ?? '';
$sqlFile = __DIR__ . '/databases/booknest.sql';
$seedFile = __DIR__ . '/data/BookNest.json';
$doSeed = isset($_GET['seed']) || isset($_POST['seed']);

$ok = false;
$message = null;
$seedCount = null;

try {
    if (!is_file($sqlFile)) {
        throw new RuntimeException('No está databases/booknest.sql');
    }
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $safeName = str_replace('`', '', (string) $name);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$safeName}`");

    $tables = $pdo->query("SHOW TABLES LIKE 'books'")->fetch();
    if (!$tables) {
        $sql = (string) file_get_contents($sqlFile);
        foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || str_starts_with($stmt, '--')) {
                continue;
            }
            if (preg_match('/^(SET|CREATE DATABASE|USE)\b/i', $stmt)) {
                continue;
            }
            $pdo->exec($stmt);
        }
        $message = 'Base creada e importada.';
    } else {
        $message = 'La base ya existía.';
    }
    $ok = true;

    if ($doSeed && is_file($seedFile)) {
        require_once __DIR__ . '/app/Database.php';
        require_once __DIR__ . '/app/helpers.php';
        require_once __DIR__ . '/app/Services/AuthorService.php';
        require_once __DIR__ . '/app/Services/BookService.php';
        require_once __DIR__ . '/app/Services/ImportExportService.php';
        Database::connect([
            'host' => $host,
            'port' => $port,
            'name' => $safeName,
            'user' => $user,
            'pass' => $pass,
            'charset' => 'utf8mb4',
        ]);
        $result = ImportExportService::importSeedFile($seedFile, true);
        $seedCount = $result['imported'];
        $message .= ' Seed: ' . $seedCount . ' volumes added to the archive.';
    }
} catch (Throwable $e) {
    $ok = false;
    $message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Instalar BookNest</title>
    <style>
        body { font-family: "IBM Plex Mono", monospace; background:#F4EDDF; color:#403845; max-width:42rem; margin:3rem auto; padding:1rem; }
        .window { background:#FFF9EE; border:2px solid #6E626A; box-shadow:4px 4px 0 rgba(64,56,69,.22); padding:1.25rem; }
        .ok { color:#61735D; } .err { color:#895B61; }
        a, button { font-family: inherit; }
        button, .btn { border:2px solid #6E626A; background:#BBA9D6; padding:.5rem .75rem; box-shadow:2px 2px 0 #6E626A; cursor:pointer; text-decoration:none; color:#403845; display:inline-block; }
    </style>
</head>
<body>
<div class="window">
    <h1>BOOKNEST INSTALL</h1>
    <?php if ($ok): ?>
        <p class="ok"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($seedCount === null && is_file($seedFile)): ?>
            <form method="post">
                <input type="hidden" name="seed" value="1">
                <p>Hay un seed en <code>data/BookNest.json</code>.</p>
                <button type="submit">Importar 217 libros (reemplaza books)</button>
            </form>
        <?php endif; ?>
        <p><a class="btn" href="index.php">Entrar a BookNest</a></p>
    <?php else: ?>
        <p class="err"><?= htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</div>
</body>
</html>
