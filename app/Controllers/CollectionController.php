<?php

declare(strict_types=1);

final class CollectionController
{
    public static function index(): void
    {
        view('collections/index', [
            'title' => 'Colecciones',
            'collections' => CollectionService::collections(),
            'series' => CollectionService::seriesList(),
        ]);
    }

    public static function showCollection(string $id): void
    {
        $item = CollectionService::findCollection((int) $id);
        if (!$item) {
            flash('error', 'Colección no encontrada.');
            redirect('/colecciones');
        }
        view('collections/show', [
            'title' => $item['name'],
            'type' => 'collection',
            'item' => $item,
            'books' => CollectionService::booksInCollection((int) $id),
        ]);
    }

    public static function showSeries(string $id): void
    {
        $item = CollectionService::findSeries((int) $id);
        if (!$item) {
            flash('error', 'Saga no encontrada.');
            redirect('/colecciones');
        }
        view('collections/show', [
            'title' => $item['name'],
            'type' => 'series',
            'item' => $item,
            'books' => CollectionService::booksInSeries((int) $id),
        ]);
    }

    public static function storeCollection(): void
    {
        require_csrf();
        $id = CollectionService::saveCollection($_POST);
        flash('success', 'Colección creada.');
        redirect('/colecciones/' . $id);
    }

    public static function updateCollection(string $id): void
    {
        require_csrf();
        CollectionService::saveCollection($_POST, (int) $id);
        flash('success', 'Colección actualizada.');
        redirect('/colecciones/' . $id);
    }

    public static function destroyCollection(string $id): void
    {
        require_csrf();
        CollectionService::deleteCollection((int) $id);
        flash('success', 'Colección eliminada.');
        redirect('/colecciones');
    }

    public static function storeSeries(): void
    {
        require_csrf();
        $id = CollectionService::saveSeries($_POST);
        flash('success', 'Saga creada.');
        redirect('/sagas/' . $id);
    }

    public static function updateSeries(string $id): void
    {
        require_csrf();
        CollectionService::saveSeries($_POST, (int) $id);
        flash('success', 'Saga actualizada.');
        redirect('/sagas/' . $id);
    }

    public static function destroySeries(string $id): void
    {
        require_csrf();
        CollectionService::deleteSeries((int) $id);
        flash('success', 'Saga eliminada.');
        redirect('/colecciones');
    }
}
