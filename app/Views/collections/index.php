<div class="window">
    <div class="window-title"><span><?= icon('collection') ?> COLLECTIONS & SERIES</span></div>
    <div class="window-body">
        <div class="split">
            <section class="panel">
                <h2 class="panel-title">COLECCIONES EDITORIALES</h2>
                <form method="post" action="<?= e(url('/colecciones')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <input name="name" required placeholder="Nombre">
                    <input type="number" name="expected_volumes" placeholder="Vol. esperados">
                    <button class="btn btn-primary" type="submit"><?= icon('add') ?></button>
                </form>
                <ul class="entity-list">
                    <?php foreach ($collections as $c): ?>
                        <li>
                            <a href="<?= e(url('/colecciones/' . $c['id'])) ?>"><?= e($c['name']) ?></a>
                            <span class="muted"><?= (int) $c['book_count'] ?><?= $c['expected_volumes'] ? ' / ' . (int) $c['expected_volumes'] : '' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <section class="panel">
                <h2 class="panel-title">SAGAS LITERARIAS</h2>
                <form method="post" action="<?= e(url('/sagas')) ?>" class="inline-form">
                    <?= csrf_field() ?>
                    <input name="name" required placeholder="Nombre">
                    <input type="number" name="expected_volumes" placeholder="Vol. esperados">
                    <button class="btn btn-primary" type="submit"><?= icon('add') ?></button>
                </form>
                <ul class="entity-list">
                    <?php foreach ($series as $s): ?>
                        <li>
                            <a href="<?= e(url('/sagas/' . $s['id'])) ?>"><?= e($s['name']) ?></a>
                            <span class="muted"><?= (int) $s['book_count'] ?><?= $s['expected_volumes'] ? ' / ' . (int) $s['expected_volumes'] : '' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>
    </div>
</div>
