<div class="window">
    <div class="window-title"><span><?= icon('import') ?> IMPORT / EXPORT</span></div>
    <div class="window-body">
        <p class="muted">Actualmente hay <?= format_number($bookCount) ?> volúmenes en el archive.</p>

        <div class="split">
            <section class="panel">
                <h2 class="panel-title">EXPORT</h2>
                <div class="actions">
                    <a class="btn btn-primary" href="<?= e(url('/exportar/json')) ?>">Export JSON</a>
                    <a class="btn btn-secondary" href="<?= e(url('/exportar/csv')) ?>">Export CSV</a>
                </div>
            </section>

            <section class="panel">
                <h2 class="panel-title">IMPORT</h2>
                <form method="post" action="<?= e(url('/importar')) ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <label>Archivo JSON o CSV<input type="file" name="file" accept=".json,.csv,application/json,text/csv" required></label>
                    <label class="check"><input type="checkbox" name="replace" value="1"> Reemplazar biblioteca actual</label>
                    <button class="btn btn-magic" type="submit">Importar al archive</button>
                </form>
            </section>
        </div>
    </div>
</div>
