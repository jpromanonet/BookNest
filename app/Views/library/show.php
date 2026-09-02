<div class="window book-sheet">
    <div class="window-title book-sheet-title">
        <div class="book-sheet-heading">
            <span class="book-sheet-icon"><?= icon('book', 22) ?></span>
            <h1 class="book-sheet-name"><?= e($book['title']) ?></h1>
        </div>
        <div class="actions book-sheet-actions">
            <a class="btn btn-edit" href="<?= e(url('/biblioteca/' . $book['id'] . '/editar')) ?>">
                <?= icon('edit') ?> EDIT
            </a>
            <form method="post" action="<?= e(url('/biblioteca/' . $book['id'] . '/eliminar')) ?>" data-confirm="¿Eliminar «<?= e($book['title']) ?>»?">
                <?= csrf_field() ?>
                <button class="btn btn-danger" type="submit"><?= icon('delete') ?> DELETE</button>
            </form>
        </div>
    </div>
    <div class="window-body">
        <?php if ($book['subtitle']): ?>
            <p class="sheet-subtitle"><?= e($book['subtitle']) ?></p>
        <?php endif; ?>

        <div class="sheet-grid">
            <div class="sheet-cover">
                <img src="<?= e(cover_url($book['cover'] ?? null)) ?>" alt="Portada de <?= e($book['title']) ?>">
            </div>
            <div class="sheet-stats">
                <div class="sheet-row"><span>AUTHOR</span><strong><?= e($book['authors_text'] ?: '—') ?></strong></div>
                <div class="sheet-row"><span>PUBLISHED</span><strong><?= e((string) ($book['publication_year'] ?: ($book['publication_date'] ?: '—'))) ?></strong></div>
                <div class="sheet-row"><span>PUBLISHER</span><strong><?= e($book['publisher_name'] ?: '—') ?></strong></div>
                <div class="sheet-row"><span>PAGES</span><strong><?= e((string) ($book['pages'] ?: '—')) ?></strong></div>
                <div class="sheet-row"><span>LANGUAGE</span><strong><?= e($book['language'] ?: '—') ?></strong></div>
                <div class="sheet-row"><span>FORMAT</span><strong><?= e($book['format'] ?: '—') ?></strong></div>
                <div class="sheet-row"><span>ISBN</span><strong><?= e($book['isbn13'] ?: ($book['isbn10'] ?: '—')) ?></strong></div>
                <div class="sheet-row"><span>EDITION</span><strong><?= e($book['edition'] ?: '—') ?></strong></div>
                <div class="sheet-row"><span>COLLECTION</span><strong><?= e($book['collection_name'] ?: '—') ?></strong></div>
                <div class="sheet-row"><span>SERIES</span><strong><?= e(($book['series_name'] ?: '—') . ($book['series_number'] ? ' #' . $book['series_number'] : '')) ?></strong></div>
                <div class="sheet-row"><span>STATUS</span><strong><span class="badge <?= e(status_badge_class($book['reading_status'])) ?>">[ <?= e(strtoupper(status_label($book['reading_status']))) ?> ]</span></strong></div>
                <div class="sheet-row"><span>CONDITION</span><strong><?= e(condition_label($book['physical_condition'])) ?></strong></div>
                <div class="sheet-row"><span>RATING</span><strong><?= $book['rating'] ? str_repeat('★', (int)$book['rating']) . str_repeat('☆', 5 - (int)$book['rating']) : '—' ?></strong></div>
                <div class="sheet-row"><span>VALUE</span><strong><?= format_money($book['estimated_value'] !== null ? (float)$book['estimated_value'] : null) ?></strong></div>
            </div>
        </div>

        <?php if ($book['genres_text'] || $book['tags_text']): ?>
            <div class="chip-row">
                <?php foreach ($book['genres'] as $g): ?>
                    <span class="badge badge-lavender">[ <?= e(strtoupper($g['name'])) ?> ]</span>
                <?php endforeach; ?>
                <?php foreach ($book['tags'] as $t): ?>
                    <span class="badge badge-parchment">[ <?= e($t['name']) ?> ]</span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="panel mt-4">
            <h2 class="panel-title">DESCRIPTION</h2>
            <div class="editorial-text"><?= nl2br(e($book['description'] ?: 'Sin descripción.')) ?></div>
        </section>

        <?php if ($book['notes'] || $book['reading_comment']): ?>
            <section class="panel mt-4">
                <h2 class="panel-title">NOTES</h2>
                <?php if ($book['reading_comment']): ?>
                    <p class="editorial-text"><?= nl2br(e($book['reading_comment'])) ?></p>
                <?php endif; ?>
                <?php if ($book['notes']): ?>
                    <p class="editorial-text"><?= nl2br(e($book['notes'])) ?></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($book['goodreads_url']): ?>
            <p class="mt-3"><a href="<?= e($book['goodreads_url']) ?>" target="_blank" rel="noopener">Open on Goodreads ↗</a></p>
        <?php endif; ?>
    </div>
</div>
