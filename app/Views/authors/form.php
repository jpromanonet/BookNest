<?php $isEdit = $author !== null; ?>
<div class="window">
    <div class="window-title"><span><?= icon('author') ?> <?= $isEdit ? 'EDIT AUTHOR' : 'NEW AUTHOR' ?></span></div>
    <div class="window-body">
        <form method="post" action="<?= e(url($isEdit ? '/autores/' . $author['id'] : '/autores')) ?>">
            <?= csrf_field() ?>
            <div class="form-grid">
                <label class="span-2">Nombre *<input required name="name" value="<?= e($author['name'] ?? '') ?>"></label>
                <label>Nacionalidad<input name="nationality" value="<?= e($author['nationality'] ?? '') ?>"></label>
                <label>Nacimiento<input type="date" name="birth_date" value="<?= e($author['birth_date'] ?? '') ?>"></label>
                <label>Fallecimiento<input type="date" name="death_date" value="<?= e($author['death_date'] ?? '') ?>"></label>
                <label class="span-2">Biografía<textarea class="editorial" name="biography" rows="5"><?= e($author['biography'] ?? '') ?></textarea></label>
                <label class="span-2">Notas<textarea name="notes" rows="3"><?= e($author['notes'] ?? '') ?></textarea></label>
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">[ SAVE ]</button>
                <a class="btn btn-secondary" href="<?= e(url('/autores')) ?>">[ CANCEL ]</a>
            </div>
        </form>
    </div>
</div>
