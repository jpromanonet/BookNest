<?php
$page = (int) $result['page'];
$pages = (int) $result['pages'];
$authors = $result['items'];
$q = (string) ($result['q'] ?? '');
$qs = static function (array $extra = []) use ($q): string {
    $params = array_filter(array_merge(['q' => $q], $extra), static fn ($v) => $v !== '' && $v !== null);
    return http_build_query($params);
};
$windowStart = max(1, $page - 4);
$windowEnd = min($pages, $page + 4);
?>
<div class="window">
    <div class="window-title">
        <span><?= icon('author') ?> AUTHORS</span>
        <a class="btn btn-primary" href="<?= e(url('/autores/nuevo')) ?>"><?= icon('add') ?> NEW</a>
    </div>
    <div class="window-body">
        <form class="toolbar" method="get" action="<?= e(url('/autores')) ?>">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar autor…">
            <button class="btn btn-secondary" type="submit"><?= icon('search') ?> SEEK</button>
        </form>

        <p class="muted mt-2"><?= format_number($result['total']) ?> authors · 20 por página · página <?= $page ?> / <?= max(1, $pages) ?></p>

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

            <?php if ($pages > 1): ?>
                <nav class="pagination">
                    <?php if ($page > 1): ?>
                        <a class="btn btn-secondary" href="<?= e(url('/autores?' . $qs(['page' => $page - 1]))) ?>">← Prev</a>
                    <?php endif; ?>
                    <?php if ($windowStart > 1): ?>
                        <a class="btn btn-secondary" href="<?= e(url('/autores?' . $qs(['page' => 1]))) ?>">1</a>
                        <?php if ($windowStart > 2): ?><span class="muted">…</span><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($p = $windowStart; $p <= $windowEnd; $p++): ?>
                        <a class="btn <?= $p === $page ? 'btn-primary' : 'btn-secondary' ?>" href="<?= e(url('/autores?' . $qs(['page' => $p]))) ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <?php if ($windowEnd < $pages): ?>
                        <?php if ($windowEnd < $pages - 1): ?><span class="muted">…</span><?php endif; ?>
                        <a class="btn btn-secondary" href="<?= e(url('/autores?' . $qs(['page' => $pages]))) ?>"><?= $pages ?></a>
                    <?php endif; ?>
                    <?php if ($page < $pages): ?>
                        <a class="btn btn-secondary" href="<?= e(url('/autores?' . $qs(['page' => $page + 1]))) ?>">Next →</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
