<?php

declare(strict_types=1);

final class AuthorController
{
    public static function index(): void
    {
        view('authors/index', [
            'title' => 'Autores',
            'authors' => AuthorService::all(),
        ]);
    }

    public static function create(): void
    {
        view('authors/form', ['title' => 'Nuevo autor', 'author' => null]);
    }

    public static function store(): void
    {
        require_csrf();
        $id = AuthorService::create($_POST);
        flash('success', 'Autor registrado.');
        redirect('/autores/' . $id);
    }

    public static function show(string $id): void
    {
        $author = AuthorService::find((int) $id);
        if (!$author) {
            flash('error', 'Autor no encontrado.');
            redirect('/autores');
        }
        view('authors/show', [
            'title' => $author['name'],
            'author' => $author,
            'books' => AuthorService::books((int) $id),
            'stats' => AuthorService::stats((int) $id),
        ]);
    }

    public static function edit(string $id): void
    {
        $author = AuthorService::find((int) $id);
        if (!$author) {
            flash('error', 'Autor no encontrado.');
            redirect('/autores');
        }
        view('authors/form', ['title' => 'Editar autor', 'author' => $author]);
    }

    public static function update(string $id): void
    {
        require_csrf();
        AuthorService::update((int) $id, $_POST);
        flash('success', 'Autor actualizado.');
        redirect('/autores/' . $id);
    }

    public static function destroy(string $id): void
    {
        require_csrf();
        AuthorService::delete((int) $id);
        flash('success', 'Autor eliminado.');
        redirect('/autores');
    }
}
