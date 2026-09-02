<?php
$isEdit = $book !== null;
$v = static function (string $key, mixed $default = '') use ($book) {
    return e((string) ($book[$key] ?? $default));
};
?>
<div class="window">
    <div class="window-title">
        <span><?= icon('book') ?> <?= $isEdit ? 'EDIT VOLUME' : 'ADD VOLUME' ?></span>
    </div>
    <div class="window-body">
        <form method="post" enctype="multipart/form-data" action="<?= e(url($isEdit ? '/biblioteca/' . $book['id'] : '/biblioteca')) ?>" class="book-form" id="book-form">
            <?= csrf_field() ?>

            <section class="panel goodreads-panel">
                <h2 class="panel-title">✦ SEEK BOOK (GOODREADS)</h2>
                <div class="toolbar">
                    <input type="text" id="goodreads-query" placeholder="ISBN, título, título+autor o URL de Goodreads" autocomplete="off">
                    <button type="button" class="btn btn-magic" id="seek-goodreads" data-url="<?= e(url('/goodreads')) ?>">✦ FETCH GOODREADS DATA</button>
                </div>
                <p class="muted" id="goodreads-status">Consulting the archives when you seek…</p>
            </section>

            <div class="form-sections">
                <section class="panel">
                    <h2 class="panel-title">IDENTIDAD</h2>
                    <div class="form-grid">
                        <label class="span-2">Título *<input required name="title" id="field-title" value="<?= $v('title') ?>"></label>
                        <label class="span-2">Subtítulo<input name="subtitle" id="field-subtitle" value="<?= $v('subtitle') ?>"></label>
                        <label class="span-2">Autor(es)<input name="authors_text" id="field-authors_text" value="<?= $v('authors_text') ?>" placeholder="Separar con coma"></label>
                        <label>ISBN-10<input name="isbn10" id="field-isbn10" value="<?= $v('isbn10') ?>"></label>
                        <label>ISBN-13<input name="isbn13" id="field-isbn13" value="<?= $v('isbn13') ?>"></label>
                        <label>Editorial
                            <input list="publishers-list" name="publisher_name" id="field-publisher" value="<?= e($book['publisher_name'] ?? '') ?>">
                            <datalist id="publishers-list">
                                <?php foreach ($options['publishers'] as $p): ?>
                                    <option value="<?= e($p['name']) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </label>
                        <label>Año<input type="number" name="publication_year" id="field-publication_year" value="<?= $v('publication_year') ?>"></label>
                        <label>Fecha publicación<input type="date" name="publication_date" value="<?= $v('publication_date') ?>"></label>
                        <label>Edición<input name="edition" value="<?= $v('edition') ?>"></label>
                        <label>Volumen<input name="volume" value="<?= $v('volume') ?>"></label>
                        <label>Colección
                            <input list="collections-list" name="collection_name" value="<?= e($book['collection_name'] ?? '') ?>">
                            <datalist id="collections-list">
                                <?php foreach ($options['collections'] as $c): ?><option value="<?= e($c['name']) ?>"><?php endforeach; ?>
                            </datalist>
                        </label>
                        <label>Saga / serie
                            <input list="series-list" name="series_name" id="field-series_name" value="<?= e($book['series_name'] ?? '') ?>">
                            <datalist id="series-list">
                                <?php foreach ($options['series'] as $s): ?><option value="<?= e($s['name']) ?>"><?php endforeach; ?>
                            </datalist>
                        </label>
                        <label>Nº en saga<input name="series_number" id="field-series_number" value="<?= $v('series_number') ?>"></label>
                        <label>Idioma<input name="language" id="field-language" value="<?= $v('language') ?>"></label>
                        <label>Páginas<input type="number" name="pages" id="field-pages" value="<?= $v('pages') ?>"></label>
                        <label>Formato<input name="format" id="field-format" value="<?= $v('format') ?>"></label>
                        <label class="span-2">Géneros<input name="genres_text" id="field-genres_text" value="<?= $v('genres_text') ?>" placeholder="Separar con coma"></label>
                        <label class="span-2">Tags<input name="tags_text" value="<?= $v('tags_text') ?>" placeholder="programación, firmado…"></label>
                        <label class="span-2">URL Goodreads<input name="goodreads_url" id="field-goodreads_url" value="<?= $v('goodreads_url') ?>"></label>
                    </div>
                </section>

                <section class="panel">
                    <h2 class="panel-title">EJEMPLAR</h2>
                    <div class="form-grid">
                        <label>Estado físico
                            <select name="physical_condition">
                                <?php foreach (physical_conditions() as $k => $label): ?>
                                    <option value="<?= e($k) ?>" <?= (($book['physical_condition'] ?? 'good') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Fecha adquisición<input type="date" name="purchase_date" value="<?= $v('purchase_date') ?>"></label>
                        <label>Precio pagado<input type="number" step="0.01" name="purchase_price" value="<?= $v('purchase_price') ?>"></label>
                        <label>Lugar de compra<input name="purchase_place" value="<?= $v('purchase_place') ?>"></label>
                        <label>Valor estimado<input type="number" step="0.01" name="estimated_value" value="<?= $v('estimated_value') ?>"></label>
                        <label>Portada (URL)<input name="cover_url" id="field-cover" value="<?= e(str_starts_with((string)($book['cover'] ?? ''), 'http') ? (string)$book['cover'] : '') ?>"></label>
                        <label class="span-2">Subir portada<input type="file" name="cover_file" accept="image/*"></label>
                    </div>
                </section>

                <section class="panel">
                    <h2 class="panel-title">LECTURA</h2>
                    <div class="form-grid">
                        <label>Estado
                            <select name="reading_status">
                                <?php foreach (reading_statuses() as $k => $label): ?>
                                    <option value="<?= e($k) ?>" <?= (($book['reading_status'] ?? 'unread') === $k) ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Inicio<input type="date" name="reading_started_at" value="<?= $v('reading_started_at') ?>"></label>
                        <label>Fin<input type="date" name="reading_finished_at" value="<?= $v('reading_finished_at') ?>"></label>
                        <label>Puntuación (1-5)<input type="number" min="1" max="5" name="rating" value="<?= $v('rating') ?>"></label>
                        <label class="span-2">Comentario<textarea name="reading_comment" rows="3"><?= $v('reading_comment') ?></textarea></label>
                    </div>
                </section>

                <section class="panel">
                    <h2 class="panel-title">DESCRIPCIÓN Y NOTAS</h2>
                    <label>Descripción<textarea class="editorial" name="description" id="field-description" rows="6"><?= $v('description') ?></textarea></label>
                    <label class="mt-2">Notas personales<textarea class="editorial" name="notes" rows="4"><?= $v('notes') ?></textarea></label>
                </section>
            </div>

            <div class="actions sticky-actions">
                <button class="btn btn-primary" type="submit">[ SAVE BOOK ]</button>
                <a class="btn btn-secondary" href="<?= e(url($isEdit ? '/biblioteca/' . $book['id'] : '/biblioteca')) ?>">[ CANCEL ]</a>
            </div>
        </form>
    </div>
</div>
