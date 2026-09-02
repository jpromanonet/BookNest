<?php
/** @var string $templateFile */
/** @var string $appName */
/** @var string $appVersion */
/** @var string $title */
$bookCount = 0;
try {
    $bookCount = BookService::countAll();
} catch (Throwable $e) {
    $bookCount = 0;
}
$flashes = take_flashes();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? '') !== '' ? $title . ' · ' . $appName : $appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Pixelify+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/css/app.css') ?>">
    <link rel="icon" href="<?= e(url('/assets/icons/nest.svg')) ?>" type="image/svg+xml">
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <div class="topbar-brand-zone">
            <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Menú" aria-expanded="false"><?= icon('library', 18) ?></button>
            <a class="topbar-brand brand" href="<?= e(url('/')) ?>">
                <?= icon('nest', 22) ?>
                <span class="brand-text">BOOKNEST</span>
            </a>
        </div>
        <div class="topbar-main">
            <div class="topbar-search">
                <form class="global-search" action="<?= e(url('/buscar')) ?>" method="get">
                    <input type="search" name="q" id="global-search" placeholder="Buscar en los archivos…" value="<?= e($_GET['q'] ?? '') ?>" data-url="<?= e(url('/buscar')) ?>" autocomplete="off">
                </form>
            </div>
            <div class="topbar-actions">
                <span class="meta-pill"><?= format_number($bookCount) ?> BOOKS</span>
                <a class="icon-btn" href="<?= e(url('/configuracion')) ?>" title="Configuración"><?= icon('settings', 18) ?></a>
            </div>
        </div>
    </header>

    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-nav side-nav">
            <a class="<?= e(nav_active('/', true)) ?>" href="<?= e(url('/')) ?>"><?= icon('dashboard') ?> Dashboard</a>
            <a class="<?= e(nav_active('/biblioteca')) ?>" href="<?= e(url('/biblioteca')) ?>"><?= icon('library') ?> Biblioteca</a>
            <a class="<?= e(nav_active('/autores')) ?>" href="<?= e(url('/autores')) ?>"><?= icon('author') ?> Autores</a>
            <a class="<?= e(nav_active('/colecciones')) ?>" href="<?= e(url('/colecciones')) ?>"><?= icon('collection') ?> Colecciones</a>
            <a class="<?= e(nav_active('/wishlist')) ?>" href="<?= e(url('/wishlist')) ?>"><?= icon('wishlist') ?> Wishlist</a>
            <a class="<?= e(nav_active('/estadisticas')) ?>" href="<?= e(url('/estadisticas')) ?>"><?= icon('statistics') ?> Estadísticas</a>
            <a class="<?= e(nav_active('/perfil')) ?>" href="<?= e(url('/perfil')) ?>"><?= icon('profile') ?> Perfil lector</a>
            <a class="<?= e(nav_active('/importar-exportar')) ?>" href="<?= e(url('/importar-exportar')) ?>"><?= icon('import') ?> Importar / Exportar</a>
            <a class="<?= e(nav_active('/configuracion')) ?>" href="<?= e(url('/configuracion')) ?>"><?= icon('settings') ?> Configuración</a>
        </nav>
    </aside>
    <div class="sidebar-backdrop" aria-hidden="true"></div>

    <main class="content">
        <?php if ($flashes): ?>
            <div class="flash-stack flashes">
                <?php foreach ($flashes as $flash): ?>
                    <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php require $templateFile; ?>
    </main>

    <footer class="statusbar">
        <span>BookNest v<?= e($appVersion) ?></span>
        <span>Library: Home</span>
        <span><?= e(strtoupper((string) ($title ?? 'ARCHIVE'))) ?></span>
    </footer>
</div>
<button type="button" class="back-to-top" id="back-to-top" aria-label="Volver arriba" title="Volver arriba" hidden>
    <?= icon('up', 18) ?>
</button>
<script src="<?= e(url('/assets/js/chart.umd.min.js')) ?>"></script>
<script src="<?= e(url('/assets/js/app.js')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/js/app.js') ?>"></script>
</body>
</html>
