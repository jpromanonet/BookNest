<div class="window">
    <div class="window-title"><span><?= icon('wishlist') ?> WISHLIST</span></div>
    <div class="window-body">
        <form method="post" action="<?= e(url('/wishlist')) ?>" class="panel mb-4">
            <?= csrf_field() ?>
            <h2 class="panel-title">ADD DESIRE</h2>
            <div class="form-grid">
                <label>Título *<input required name="title"></label>
                <label>Autor<input name="author"></label>
                <label>ISBN<input name="isbn"></label>
                <label>Edición deseada<input name="desired_edition"></label>
                <label>Editorial<input name="publisher"></label>
                <label>Prioridad (1-5)<input type="number" min="1" max="5" name="priority" value="3"></label>
                <label>Precio encontrado<input type="number" step="0.01" name="found_price"></label>
                <label>Sitio / comercio<input name="store"></label>
                <label class="span-2">URL<input name="url"></label>
                <label class="span-2">Notas<textarea name="notes" rows="2"></textarea></label>
            </div>
            <button class="btn btn-primary" type="submit">[ SAVE ]</button>
        </form>

        <?php if (empty($items)): ?>
            <p class="muted">La wishlist está vacía.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                    <tr>
                        <th>TITLE</th>
                        <th>AUTHOR</th>
                        <th>PRIORITY</th>
                        <th>PRICE</th>
                        <th>STORE</th>
                        <th>ACTIONS</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['title']) ?></td>
                            <td><?= e($item['author'] ?: '—') ?></td>
                            <td><?= (int) $item['priority'] ?></td>
                            <td><?= format_money($item['found_price'] !== null ? (float)$item['found_price'] : null) ?></td>
                            <td><?= e($item['store'] ?: '—') ?></td>
                            <td class="actions-cell">
                                <form method="post" action="<?= e(url('/wishlist/' . $item['id'] . '/mover')) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-magic" type="submit">Move to library</button>
                                </form>
                                <form method="post" action="<?= e(url('/wishlist/' . $item['id'] . '/eliminar')) ?>" data-confirm="¿Eliminar de wishlist?">
                                    <?= csrf_field() ?>
                                    <button class="icon-btn danger" type="submit"><?= icon('delete') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
