<div class="window">
    <div class="window-title"><span><?= icon('settings') ?> SETTINGS</span></div>
    <div class="window-body">
        <section class="panel panel-lavender">
            <h2 class="panel-title">LIBRARY</h2>
            <form method="post" action="<?= e(url('/configuracion')) ?>">
                <?= csrf_field() ?>
                <div class="form-grid">
                    <label>Nombre de la biblioteca<input name="library_name" value="<?= e($settings['library_name'] ?? 'Home') ?>"></label>
                    <label>Idioma por defecto<input name="default_language" value="<?= e($settings['default_language'] ?? 'Español') ?>"></label>
                    <label>Moneda<input name="currency" value="<?= e($settings['currency'] ?? 'ARS') ?>"></label>
                </div>
                <button class="btn btn-primary" type="submit">[ SAVE ]</button>
            </form>
        </section>

        <section class="panel panel-blue mt-4">
            <h2 class="panel-title">✦ GOODREADS ARCHIVE SYNC</h2>
            <p class="muted">Consulta la metadata pública de Goodreads para completar páginas, portadas, ISBNs, géneros y más. El valor estimado no viene de Goodreads: se puede cargar a mano en cada ficha.</p>
            <div class="enrich-box">
                <p><strong id="enrich-pending"><?= (int) ($enrichPending ?? 0) ?></strong> volúmenes sin páginas o sin URL de Goodreads.</p>
                <div class="progress-bar enrich-progress" aria-label="0%">
                    <?php for ($i = 0; $i < 20; $i++): ?>
                        <span class="progress-block" data-enrich-block></span>
                    <?php endfor; ?>
                    <span class="progress-pct" id="enrich-pct">0%</span>
                </div>
                <p class="muted" id="enrich-status">Listo para consultar los archivos…</p>
                <div class="actions mt-3">
                    <button type="button" class="btn btn-magic" id="enrich-start"
                            data-url="<?= e(url('/goodreads/enrich')) ?>"
                            data-csrf="<?= e(csrf_token()) ?>">
                        ✦ SYNC MISSING METADATA
                    </button>
                    <button type="button" class="btn btn-secondary" id="enrich-all"
                            data-url="<?= e(url('/goodreads/enrich')) ?>"
                            data-csrf="<?= e(csrf_token()) ?>"
                            data-mode="all">
                        ✦ RE-SYNC ALL BOOKS
                    </button>
                </div>
            </div>
        </section>
    </div>
</div>
