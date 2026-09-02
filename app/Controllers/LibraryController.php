<?php

declare(strict_types=1);

final class LibraryController
{
    public static function index(): void
    {
        $filters = [
            'q' => $_GET['q'] ?? '',
            'author_id' => $_GET['author_id'] ?? '',
            'publisher_id' => $_GET['publisher_id'] ?? '',
            'genre_id' => $_GET['genre_id'] ?? '',
            'language' => $_GET['language'] ?? '',
            'year' => $_GET['year'] ?? '',
            'decade' => $_GET['decade'] ?? '',
            'series_id' => $_GET['series_id'] ?? '',
            'collection_id' => $_GET['collection_id'] ?? '',
            'reading_status' => $_GET['reading_status'] ?? '',
            'physical_condition' => $_GET['physical_condition'] ?? '',
            'format' => $_GET['format'] ?? '',
            'has_isbn' => $_GET['has_isbn'] ?? '',
            'has_cover' => $_GET['has_cover'] ?? '',
        ];
        $sort = (string) ($_GET['sort'] ?? 'title');
        $dir = (string) ($_GET['dir'] ?? 'asc');
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = BookService::search($filters, $sort, $dir, $page, 20);

        view('library/index', [
            'title' => 'Biblioteca',
            'result' => $result,
            'filters' => $filters,
            'sort' => $sort,
            'dir' => $dir,
            'options' => BookService::filterOptions(),
        ]);
    }

    public static function create(): void
    {
        view('library/form', [
            'title' => 'Agregar libro',
            'book' => null,
            'options' => BookService::filterOptions(),
        ]);
    }

    public static function store(): void
    {
        require_csrf();
        try {
            $data = self::fromRequest();
            $data['cover'] = BookService::handleCoverUpload($_FILES['cover_file'] ?? null, $_POST['cover_url'] ?? null);
            $id = BookService::create($data);
            flash('success', 'Volume added to the archive.');
            redirect('/biblioteca/' . $id);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/biblioteca/nuevo');
        }
    }

    public static function show(string $id): void
    {
        $book = BookService::find((int) $id);
        if (!$book) {
            flash('error', 'No books found in the archives.');
            redirect('/biblioteca');
        }
        view('library/show', [
            'title' => $book['title'],
            'book' => $book,
        ]);
    }

    public static function edit(string $id): void
    {
        $book = BookService::find((int) $id);
        if (!$book) {
            flash('error', 'No books found in the archives.');
            redirect('/biblioteca');
        }
        view('library/form', [
            'title' => 'Editar libro',
            'book' => $book,
            'options' => BookService::filterOptions(),
        ]);
    }

    public static function update(string $id): void
    {
        require_csrf();
        $book = BookService::find((int) $id);
        if (!$book) {
            flash('error', 'No books found in the archives.');
            redirect('/biblioteca');
        }
        try {
            $data = self::fromRequest();
            $data['cover'] = BookService::handleCoverUpload(
                $_FILES['cover_file'] ?? null,
                $_POST['cover_url'] ?? null,
                $book['cover'] ?? null
            );
            BookService::update((int) $id, $data);
            flash('success', 'Ficha actualizada.');
            redirect('/biblioteca/' . $id);
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
            redirect('/biblioteca/' . $id . '/editar');
        }
    }

    public static function destroy(string $id): void
    {
        require_csrf();
        BookService::delete((int) $id);
        flash('success', 'Ejemplar eliminado del archive.');
        redirect('/biblioteca');
    }

    public static function updateStatus(string $id): void
    {
        require_csrf();
        $status = (string) ($_POST['reading_status'] ?? '');
        $allowed = array_keys(reading_statuses());
        if (!in_array($status, $allowed, true)) {
            json_response(['ok' => false, 'error' => 'Estado inválido.'], 422);
        }

        $book = BookService::find((int) $id);
        if (!$book) {
            json_response(['ok' => false, 'error' => 'Libro no encontrado.'], 404);
        }

        BookService::updateReadingStatus((int) $id, $status);
        json_response([
            'ok' => true,
            'id' => (int) $id,
            'reading_status' => $status,
            'label' => status_label($status),
            'badge_class' => status_badge_class($status),
        ]);
    }

    private static function fromRequest(): array
    {
        return [
            'title' => $_POST['title'] ?? '',
            'subtitle' => $_POST['subtitle'] ?? null,
            'authors_text' => $_POST['authors_text'] ?? '',
            'isbn10' => $_POST['isbn10'] ?? null,
            'isbn13' => $_POST['isbn13'] ?? null,
            'publisher_id' => $_POST['publisher_id'] ?? null,
            'publisher_name' => $_POST['publisher_name'] ?? null,
            'publication_date' => $_POST['publication_date'] ?? null,
            'publication_year' => $_POST['publication_year'] ?? null,
            'edition' => $_POST['edition'] ?? null,
            'volume' => $_POST['volume'] ?? null,
            'collection_id' => $_POST['collection_id'] ?? null,
            'collection_name' => $_POST['collection_name'] ?? null,
            'series_id' => $_POST['series_id'] ?? null,
            'series_name' => $_POST['series_name'] ?? null,
            'series_number' => $_POST['series_number'] ?? null,
            'language' => $_POST['language'] ?? null,
            'pages' => $_POST['pages'] ?? null,
            'format' => $_POST['format'] ?? null,
            'description' => $_POST['description'] ?? null,
            'physical_condition' => $_POST['physical_condition'] ?? 'good',
            'reading_status' => $_POST['reading_status'] ?? 'unread',
            'goodreads_url' => $_POST['goodreads_url'] ?? null,
            'purchase_date' => $_POST['purchase_date'] ?? null,
            'purchase_price' => $_POST['purchase_price'] ?? null,
            'purchase_place' => $_POST['purchase_place'] ?? null,
            'estimated_value' => $_POST['estimated_value'] ?? null,
            'rating' => $_POST['rating'] ?? null,
            'reading_started_at' => $_POST['reading_started_at'] ?? null,
            'reading_finished_at' => $_POST['reading_finished_at'] ?? null,
            'reading_comment' => $_POST['reading_comment'] ?? null,
            'notes' => $_POST['notes'] ?? null,
            'genres_text' => $_POST['genres_text'] ?? '',
            'tags_text' => $_POST['tags_text'] ?? '',
        ];
    }
}
