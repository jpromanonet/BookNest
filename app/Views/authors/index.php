<div class="window">
    <div class="window-title">
        <span><?= icon('author') ?> AUTHORS</span>
        <a class="btn btn-primary" href="<?= e(url('/autores/nuevo')) ?>"><?= icon('add') ?> NEW</a>
    </div>
    <div class="window-body">
        <?php if (empty($authors)): ?>
            <p class="muted">No authors in the archives yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>NAME</th>
                        <th>NATIONALITY</th>
                        <th>BOOKS</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($authors as $author): ?>
                        <tr>
                            <td><a href="<?= e(url('/autores/' . $author['id'])) ?>"><?= e($author['name']) ?></a></td>
                            <td><?= e($author['nationality'] ?: '—') ?></td>
                            <td><?= (int) $author['book_count'] ?></td>
                            <td><a class="icon-btn" href="<?= e(url('/autores/' . $author['id'] . '/editar')) ?>"><?= icon('edit') ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
