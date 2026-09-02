<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

$router = new Router();

$router->get('/', [DashboardController::class, 'index']);

$router->get('/biblioteca', [LibraryController::class, 'index']);
$router->get('/biblioteca/nuevo', [LibraryController::class, 'create']);
$router->post('/biblioteca', [LibraryController::class, 'store']);
$router->get('/biblioteca/{id}', [LibraryController::class, 'show']);
$router->get('/biblioteca/{id}/editar', [LibraryController::class, 'edit']);
$router->post('/biblioteca/{id}', [LibraryController::class, 'update']);
$router->post('/biblioteca/{id}/status', [LibraryController::class, 'updateStatus']);
$router->post('/biblioteca/{id}/eliminar', [LibraryController::class, 'destroy']);

$router->get('/autores', [AuthorController::class, 'index']);
$router->get('/autores/nuevo', [AuthorController::class, 'create']);
$router->post('/autores', [AuthorController::class, 'store']);
$router->get('/autores/{id}', [AuthorController::class, 'show']);
$router->get('/autores/{id}/editar', [AuthorController::class, 'edit']);
$router->post('/autores/{id}', [AuthorController::class, 'update']);
$router->post('/autores/{id}/eliminar', [AuthorController::class, 'destroy']);

$router->get('/colecciones', [CollectionController::class, 'index']);
$router->post('/colecciones', [CollectionController::class, 'storeCollection']);
$router->get('/colecciones/{id}', [CollectionController::class, 'showCollection']);
$router->post('/colecciones/{id}', [CollectionController::class, 'updateCollection']);
$router->post('/colecciones/{id}/eliminar', [CollectionController::class, 'destroyCollection']);
$router->post('/sagas', [CollectionController::class, 'storeSeries']);
$router->get('/sagas/{id}', [CollectionController::class, 'showSeries']);
$router->post('/sagas/{id}', [CollectionController::class, 'updateSeries']);
$router->post('/sagas/{id}/eliminar', [CollectionController::class, 'destroySeries']);

$router->get('/wishlist', [WishlistController::class, 'index']);
$router->post('/wishlist', [WishlistController::class, 'store']);
$router->post('/wishlist/{id}', [WishlistController::class, 'update']);
$router->post('/wishlist/{id}/eliminar', [WishlistController::class, 'destroy']);
$router->post('/wishlist/{id}/mover', [WishlistController::class, 'move']);

$router->get('/estadisticas', [StatisticsController::class, 'index']);
$router->get('/perfil', [ProfileController::class, 'index']);

$router->get('/importar-exportar', [ImportExportController::class, 'index']);
$router->get('/exportar/json', [ImportExportController::class, 'exportJson']);
$router->get('/exportar/csv', [ImportExportController::class, 'exportCsv']);
$router->get('/exportar/pdf', [ImportExportController::class, 'exportPdf']);
$router->post('/importar', [ImportExportController::class, 'import']);

$router->get('/configuracion', [SettingsController::class, 'index']);
$router->post('/configuracion', [SettingsController::class, 'save']);

$router->get('/buscar', [SearchController::class, 'index']);
$router->post('/goodreads', [GoodreadsController::class, 'lookup']);
$router->post('/goodreads/enrich', [GoodreadsController::class, 'enrichNext']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
