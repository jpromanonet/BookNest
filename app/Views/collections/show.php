<?php
$isSeries = ($type ?? '') === 'series';
$base = $isSeries ? '/sagas/' : '/colecciones/';
$expected = (int) ($item['expected_volumes'] ?? 0);
$count = (int) ($item['book_count'] ?? count($books));
?>
<div class="window">
    <div class="window-title">
        <span><?= icon('collection') ?> <?= e(strtoupper($item['name'])) ?></span>
        <form method="post" action="<?= e(url($base . $item['id'] . '/eliminar')) ?>" data-confirm="¿Eliminar?">
            <?= csrf_field() ?>
            <button class="btn btn-danger" type="submit"><?= icon('delete') ?></button>
        </form>
    </div>
    <div class="window-body">
        <p class="muted">
            <?= $count ?><?= $expected ? ' de ' . $expected : '' ?> libros registrados
            <?php if ($expected && $count < $expected): ?> · <span class="badge badge-rose">[ INCOMPLETE ]</span><?php endif; ?>
        </p>

        <form method="post" action="<?= e(url($base . $item['id'])) ?>" class="inline-form mb-3">
            <?= csrf_field() ?>
            <input name="name" value="<?= e($item['name']) ?>" required>
            <input type="number" name="expected_volumes" value="<?= e((string) ($item['expected_volumes'] ?? '')) ?>" placeholder="Esperados">
            <input name="description" value="<?= e($item['description'] ?? '') ?>" placeholder="Descripción">
            <button class="btn btn-secondary" type="submit">Save</button>
        </form>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th></th>
                    <th><?= $isSeries ? '#' : 'VOL' ?></th>
                    <th>TITLE</th>
                    <th>AUTHOR</th>
                    <th>STATUS</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><img class="cover-thumb" src="<?= e(cover_url($book['cover'] ?? null)) ?>" alt=""></td>
                        <td><?= e((string) ($isSeries ? ($book['series_number'] ?? '—') : ($book['volume'] ?? '—'))) ?></td>
                        <td><a href="<?= e(url('/biblioteca/' . $book['id'])) ?>"><?= e($book['title']) ?></a></td>
                        <td><?= e($book['author_names'] ?? '—') ?></td>
                        <td><span class="badge <?= e(status_badge_class($book['reading_status'])) ?>">[ <?= e(strtoupper(status_label($book['reading_status']))) ?> ]</span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
