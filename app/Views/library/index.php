<?php
$qs = static function (array $extra = []) use ($filters, $sort, $dir): string {
    $params = array_filter(array_merge($filters, ['sort' => $sort, 'dir' => $dir], $extra), static fn ($v) => $v !== '' && $v !== null);
    return http_build_query($params);
};
$sortLink = static function (string $col) use ($sort, $dir, $qs): string {
    $nextDir = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    return url('/biblioteca?' . $qs(['sort' => $col, 'dir' => $nextDir, 'page' => 1]));
};
$page = (int) $result['page'];
$pages = (int) $result['pages'];
$windowStart = max(1, $page - 4);
$windowEnd = min($pages, $page + 4);
?>
<div class="window">
    <div class="window-title">
        <span><?= icon('library') ?> LIBRARY</span>
        <a class="btn btn-primary" href="<?= e(url('/biblioteca/nuevo')) ?>"><?= icon('add') ?> ADD BOOK</a>
    </div>
    <div class="window-body">
        <form class="toolbar" method="get" action="<?= e(url('/biblioteca')) ?>">
            <input type="search" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Search: título, autor, ISBN…">
            <button type="button" class="btn btn-secondary" id="filter-toggle" data-filter-toggle>Filters ▼</button>
            <button class="btn btn-secondary" type="submit"><?= icon('search') ?> SEEK</button>
        </form>

        <form class="filter-panel" id="filter-panel" method="get" action="<?= e(url('/biblioteca')) ?>" hidden>
            <input type="hidden" name="q" value="<?= e($filters['q'] ?? '') ?>">
            <div class="form-grid">
                <label>Autor
                    <select name="author_id">
                        <option value="">Todos</option>
                        <?php foreach ($options['authors'] as $a): ?>
                            <option value="<?= (int) $a['id'] ?>" <?= ((string) ($filters['author_id'] ?? '') === (string) $a['id']) ? 'selected' : '' ?>><?= e($a['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Editorial
                    <select name="publisher_id">
                        <option value="">Todas</option>
                        <?php foreach ($options['publishers'] as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" <?= ((string) ($filters['publisher_id'] ?? '') === (string) $p['id']) ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Género
                    <select name="genre_id">
                        <option value="">Todos</option>
                        <?php foreach ($options['genres'] as $g): ?>
                            <option value="<?= (int) $g['id'] ?>" <?= ((string) ($filters['genre_id'] ?? '') === (string) $g['id']) ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Idioma
                    <select name="language">
                        <option value="">Todos</option>
                        <?php foreach ($options['languages'] as $l): ?>
                            <option value="<?= e($l['name']) ?>" <?= (($filters['language'] ?? '') === $l['name']) ? 'selected' : '' ?>><?= e($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Estado lectura
                    <select name="reading_status">
                        <option value="">Todos</option>
                        <?php foreach (reading_statuses() as $k => $label): ?>
                            <option value="<?= e($k) ?>" <?= (($filters['reading_status'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Estado físico
                    <select name="physical_condition">
                        <option value="">Todos</option>
                        <?php foreach (physical_conditions() as $k => $label): ?>
                            <option value="<?= e($k) ?>" <?= (($filters['physical_condition'] ?? '') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Colección
                    <select name="collection_id">
                        <option value="">Todas</option>
                        <?php foreach ($options['collections'] as $c): ?>
                            <option value="<?= (int) $c['id'] ?>" <?= ((string) ($filters['collection_id'] ?? '') === (string) $c['id']) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Saga
                    <select name="series_id">
                        <option value="">Todas</option>
                        <?php foreach ($options['series'] as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= ((string) ($filters['series_id'] ?? '') === (string) $s['id']) ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Año
                    <select name="year">
                        <option value="">Todos</option>
                        <?php foreach ($options['years'] as $y): ?>
                            <option value="<?= (int) $y['year'] ?>" <?= ((string) ($filters['year'] ?? '') === (string) $y['year']) ? 'selected' : '' ?>><?= (int) $y['year'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Década
                    <select name="decade">
                        <option value="">Todas</option>
                        <?php foreach ([2020,2010,2000,1990,1980,1970,1960,1950,1940,1930,1920,1910,1900] as $d): ?>
                            <option value="<?= $d ?>" <?= ((string) ($filters['decade'] ?? '') === (string) $d) ? 'selected' : '' ?>><?= $d ?>s</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>ISBN
                    <select name="has_isbn">
                        <option value="">Cualquiera</option>
                        <option value="1" <?= (($filters['has_isbn'] ?? '') === '1') ? 'selected' : '' ?>>Con ISBN</option>
                        <option value="0" <?= (($filters['has_isbn'] ?? '') === '0') ? 'selected' : '' ?>>Sin ISBN</option>
                    </select>
                </label>
                <label>Portada
                    <select name="has_cover">
                        <option value="">Cualquiera</option>
                        <option value="1" <?= (($filters['has_cover'] ?? '') === '1') ? 'selected' : '' ?>>Con portada</option>
                        <option value="0" <?= (($filters['has_cover'] ?? '') === '0') ? 'selected' : '' ?>>Sin portada</option>
                    </select>
                </label>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Aplicar filtros</button>
                <a class="btn btn-secondary" href="<?= e(url('/biblioteca')) ?>">Limpiar</a>
            </div>
        </form>

        <p class="muted mt-2"><?= format_number($result['total']) ?> books · 20 por página · página <?= $page ?> / <?= max(1, $pages) ?></p>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th></th>
                    <th><a href="<?= e($sortLink('title')) ?>">TITLE</a></th>
                    <th><a href="<?= e($sortLink('author')) ?>">AUTHOR</a></th>
                    <th><a href="<?= e($sortLink('year')) ?>">YEAR</a></th>
                    <th>EDITION</th>
                    <th>ISBN</th>
                    <th>GENRE</th>
                    <th><a href="<?= e($sortLink('pages')) ?>">PAGES</a></th>
                    <th><a href="<?= e($sortLink('status')) ?>">STATUS</a></th>
                    <th>ACTIONS</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($result['items'])): ?>
                    <tr><td colspan="10" class="muted">No books found in the archives.</td></tr>
                <?php else: ?>
                    <?php foreach ($result['items'] as $book): ?>
                        <tr>
                            <td><img class="cover-thumb" src="<?= e(cover_url($book['cover'] ?? null)) ?>" alt=""></td>
                            <td><a href="<?= e(url('/biblioteca/' . $book['id'])) ?>"><?= e($book['title']) ?></a></td>
                            <td><?= e($book['author_names'] ?? '—') ?></td>
                            <td><?= e((string) ($book['publication_year'] ?? '—')) ?></td>
                            <td><?= e($book['edition'] ?? '—') ?></td>
                            <td><?= e($book['isbn13'] ?: ($book['isbn10'] ?: '—')) ?></td>
                            <td><?= e($book['genre_names'] ?? '—') ?></td>
                            <td><?= e((string) ($book['pages'] ?? '—')) ?></td>
                            <td><span class="badge <?= e(status_badge_class($book['reading_status'])) ?>">[ <?= e(strtoupper(status_label($book['reading_status']))) ?> ]</span></td>
                            <td class="actions-cell">
                                <a class="icon-btn" href="<?= e(url('/biblioteca/' . $book['id'] . '/editar')) ?>" title="Editar"><?= icon('edit') ?></a>
                                <form method="post" action="<?= e(url('/biblioteca/' . $book['id'] . '/eliminar')) ?>" data-confirm="¿Eliminar «<?= e($book['title']) ?>»?">
                                    <?= csrf_field() ?>
                                    <button class="icon-btn danger" type="submit" title="Eliminar"><?= icon('delete') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="pagination">
                <?php if ($page > 1): ?>
                    <a class="btn btn-secondary" href="<?= e(url('/biblioteca?' . $qs(['page' => $page - 1]))) ?>">← Prev</a>
                <?php endif; ?>
                <?php if ($windowStart > 1): ?>
                    <a class="btn btn-secondary" href="<?= e(url('/biblioteca?' . $qs(['page' => 1]))) ?>">1</a>
                    <?php if ($windowStart > 2): ?><span class="muted">…</span><?php endif; ?>
                <?php endif; ?>
                <?php for ($p = $windowStart; $p <= $windowEnd; $p++): ?>
                    <a class="btn <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>" href="<?= e(url('/biblioteca?' . $qs(['page' => $p]))) ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ($windowEnd < $pages): ?>
                    <?php if ($windowEnd < $pages - 1): ?><span class="muted">…</span><?php endif; ?>
                    <a class="btn btn-secondary" href="<?= e(url('/biblioteca?' . $qs(['page' => $pages]))) ?>"><?= $pages ?></a>
                <?php endif; ?>
                <?php if ($page < $pages): ?>
                    <a class="btn btn-secondary" href="<?= e(url('/biblioteca?' . $qs(['page' => $page + 1]))) ?>">Next →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</div>
