<?php

declare(strict_types=1);

final class SearchController
{
    public static function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $items = $q !== '' ? BookService::globalSearch($q, 30) : [];
        if (request_wants_json()) {
            $payload = [];
            foreach ($items as $book) {
                $payload[] = [
                    'id' => (int) $book['id'],
                    'title' => $book['title'],
                    'subtitle' => $book['author_names'] ?? '',
                    'cover' => cover_url($book['cover'] ?? null),
                    'url' => url('/biblioteca/' . $book['id']),
                ];
            }
            json_response(['q' => $q, 'items' => $payload]);
        }
        view('library/index', [
            'title' => 'Buscar',
            'result' => [
                'items' => $items,
                'total' => count($items),
                'page' => 1,
                'per_page' => 30,
                'pages' => 1,
            ],
            'filters' => array_fill_keys([
                'q','author_id','publisher_id','genre_id','language','year','decade',
                'series_id','collection_id','reading_status','physical_condition','format','has_isbn','has_cover',
            ], '') + ['q' => $q],
            'sort' => 'title',
            'dir' => 'asc',
            'options' => BookService::filterOptions(),
        ]);
    }
}
