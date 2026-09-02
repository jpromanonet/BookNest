<?php

declare(strict_types=1);

final class WishlistController
{
    public static function index(): void
    {
        view('wishlist/index', [
            'title' => 'Wishlist',
            'items' => WishlistService::all(),
        ]);
    }

    public static function store(): void
    {
        require_csrf();
        WishlistService::create($_POST);
        flash('success', 'Deseo agregado a la wishlist.');
        redirect('/wishlist');
    }

    public static function update(string $id): void
    {
        require_csrf();
        WishlistService::update((int) $id, $_POST);
        flash('success', 'Wishlist actualizada.');
        redirect('/wishlist');
    }

    public static function destroy(string $id): void
    {
        require_csrf();
        WishlistService::delete((int) $id);
        flash('success', 'Ítem eliminado.');
        redirect('/wishlist');
    }

    public static function move(string $id): void
    {
        require_csrf();
        $bookId = WishlistService::moveToLibrary((int) $id);
        flash('success', 'Volume moved into the library.');
        redirect('/biblioteca/' . $bookId . '/editar');
    }
}
