<div class="window">
    <div class="window-title">
        <span><?= icon('author') ?> <?= e(strtoupper($author['name'])) ?></span>
        <div class="actions">
            <a class="btn btn-secondary" href="<?= e(url('/autores/' . $author['id'] . '/editar')) ?>"><?= icon('edit') ?> EDIT</a>
            <form method="post" action="<?= e(url('/autores/' . $author['id'] . '/eliminar')) ?>" data-confirm="¿Eliminar autor?">
                <?= csrf_field() ?>
                <button class="btn btn-danger" type="submit"><?= icon('delete') ?> DELETE</button>
            </form>
        </div>
    </div>
    <div class="window-body">
        <section class="stat-grid compact">
            <div class="stat-block"><div class="stat-value"><?= (int) ($stats['total'] ?? 0) ?></div><div class="stat-label">BOOKS</div></div>
            <div class="stat-block accent-sage"><div class="stat-value"><?= (int) ($stats['read_count'] ?? 0) ?></div><div class="stat-label">READ</div></div>
            <div class="stat-block"><div class="stat-value"><?= (int) ($stats['unread_count'] ?? 0) ?></div><div class="stat-label">UNREAD</div></div>
            <div class="stat-block accent-blue"><div class="stat-value"><?= (int) ($stats['reading_count'] ?? 0) ?></div><div class="stat-label">READING</div></div>
        </section>

        <?php if ($author['biography']): ?>
            <section class="panel mt-4">
                <h2 class="panel-title">BIOGRAPHY</h2>
                <div class="editorial-text"><?= nl2br(e($author['biography'])) ?></div>
            </section>
        <?php endif; ?>

        <section class="panel mt-4">
            <h2 class="panel-title">EDITIONS IN LIBRARY</h2>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th></th><th>TITLE</th><th>YEAR</th><th>STATUS</th></tr></thead>
                    <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><img class="cover-thumb" src="<?= e(cover_url($book['cover'] ?? null)) ?>" alt=""></td>
                            <td><a href="<?= e(url('/biblioteca/' . $book['id'])) ?>"><?= e($book['title']) ?></a></td>
                            <td><?= e((string) ($book['publication_year'] ?? '—')) ?></td>
                            <td><span class="badge <?= e(status_badge_class($book['reading_status'])) ?>">[ <?= e(strtoupper(status_label($book['reading_status']))) ?> ]</span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
