<?php
/** @var array $profile */
/** @var array $stats */
$level = $profile['level'];
$archetype = $profile['archetype'];
$house = $profile['house'];
$omen = $profile['omen'];

$renderProse = static function (string $text): void {
    $paras = preg_split("/\n\n+/", $text) ?: [];
    foreach ($paras as $para) {
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($para));
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html ?? '');
        echo '<p>' . $html . '</p>';
    }
};
?>
<div class="window">
    <div class="window-title">
        <span><?= icon('profile') ?> PERFIL LECTOR</span>
        <span class="muted">CHARACTER SHEET</span>
    </div>
    <div class="window-body">
        <section class="profile-hero panel panel-<?= e($archetype['color']) ?>">
            <div class="profile-crest">
                <?= icon($archetype['crest'], 48) ?>
            </div>
            <div class="profile-identity">
                <p class="profile-kicker">LIBRARY ARCHETYPE · <?= e(strtoupper($house['name'])) ?></p>
                <h1 class="profile-title"><?= e($profile['title']) ?></h1>
                <p class="profile-epithet"><?= e($profile['epithet']) ?></p>
                <p class="profile-motto">«<?= e($profile['motto']) ?>»</p>
                <div class="profile-level-row">
                    <span class="badge badge-lavender">[ LVL <?= (int) $level['number'] ?> ]</span>
                    <span class="badge badge-gold">[ <?= e(strtoupper($level['rank'])) ?> ]</span>
                    <span class="muted">XP <?= format_number($level['xp']) ?></span>
                </div>
                <div class="progress-bar progress-bar-lg mt-3" aria-label="<?= (int) $level['pct'] ?>%">
                    <?php for ($i = 0; $i < 24; $i++): ?>
                        <span class="progress-block <?= $i < (int) round($level['pct'] / (100 / 24)) ? 'is-on' : '' ?>"></span>
                    <?php endfor; ?>
                    <span class="progress-pct"><?= (int) $level['pct'] ?>%</span>
                </div>
            </div>
        </section>

        <section class="stat-grid stat-grid-hero mt-4">
            <div class="stat-block tint-sage">
                <div class="stat-value"><?= (int) $profile['pct_read'] ?>%</div>
                <div class="stat-label">LEÍDOS</div>
                <div class="stat-sub"><?= format_number($stats['read']) ?> libros</div>
            </div>
            <div class="stat-block tint-parchment">
                <div class="stat-value"><?= (int) $profile['pct_unread'] ?>%</div>
                <div class="stat-label">NO LEÍDOS</div>
                <div class="stat-sub"><?= format_number($stats['unread']) ?> libros</div>
            </div>
            <div class="stat-block tint-blue">
                <div class="stat-value"><?= (int) $profile['pct_reading'] ?>%</div>
                <div class="stat-label">LEYENDO</div>
                <div class="stat-sub"><?= format_number($stats['reading']) ?> activos</div>
            </div>
            <div class="stat-block tint-rose">
                <div class="stat-value"><?= (int) $profile['pct_abandoned'] ?>%</div>
                <div class="stat-label">ABANDONADOS</div>
                <div class="stat-sub"><?= format_number($stats['abandoned']) ?> caminos</div>
            </div>
        </section>

        <div class="split mt-4">
            <section class="panel panel-lavender">
                <h2 class="panel-title">ATRIBUTOS</h2>
                <ul class="trait-list">
                    <?php foreach ($profile['traits'] as $trait): ?>
                        <li>
                            <div class="trait-head">
                                <strong><?= e($trait['name']) ?></strong>
                                <span><?= (int) $trait['value'] ?></span>
                            </div>
                            <div class="progress-bar" aria-label="<?= (int) $trait['value'] ?>">
                                <?php for ($i = 0; $i < 20; $i++): ?>
                                    <span class="progress-block <?= $i < (int) round($trait['value'] / 5) ? 'is-on' : '' ?>"></span>
                                <?php endfor; ?>
                            </div>
                            <p class="muted trait-blurb"><?= e($trait['blurb']) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="panel panel-gold">
                <h2 class="panel-title">CASA / ORDEN</h2>
                <p class="house-name"><?= e($house['name']) ?></p>
                <p class="muted house-meta">
                    Sigilo: <strong><?= e($house['sigil']) ?></strong>
                    · <?= e($house['virtue']) ?>
                </p>
                <div class="editorial-text house-blurb">
                    <?php $renderProse($house['blurb']); ?>
                </div>

                <h2 class="panel-title mt-4">AFINIDADES</h2>
                <?php if (empty($profile['affinities'])): ?>
                    <p class="muted">El oráculo aún no detecta runas de género o autor. Sincronizá metadata o etiquetá géneros.</p>
                <?php else: ?>
                    <ul class="affinity-list">
                        <?php foreach ($profile['affinities'] as $aff): ?>
                            <li>
                                <span class="badge badge-parchment">[ <?= e(strtoupper($aff['type'])) ?> ]</span>
                                <strong><?= e($aff['label']) ?></strong>
                                <span class="muted">×<?= (int) $aff['total'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h2 class="panel-title mt-4">QUEST LOG</h2>
                <ul class="quest-list">
                    <?php foreach ($profile['quest_log'] as $quest): ?>
                        <li class="quest quest-<?= e($quest['status']) ?>">
                            <strong><?= e($quest['name']) ?></strong>
                            <span class="badge <?= $quest['status'] === 'done' ? 'badge-sage' : ($quest['status'] === 'active' ? 'badge-blue' : 'badge-parchment') ?>">
                                [ <?= e(strtoupper($quest['status'])) ?> ]
                            </span>
                            <p class="muted"><?= e($quest['detail']) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>

        <div class="split mt-4">
            <section class="panel panel-sage">
                <h2 class="panel-title">BENDICIONES Y CARGAS</h2>
                <ul class="boon-list">
                    <?php foreach ($profile['boons'] as $boon): ?>
                        <li class="boon boon-<?= e($boon['kind'] === 'carga' ? 'burden' : 'bless') ?>">
                            <div class="boon-head">
                                <span class="badge <?= $boon['kind'] === 'carga' ? 'badge-rose' : 'badge-sage' ?>">
                                    [ <?= e(strtoupper($boon['kind'])) ?> ]
                                </span>
                                <strong><?= e($boon['name']) ?></strong>
                            </div>
                            <p class="muted"><?= e($boon['detail']) ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="panel panel-blue">
                <h2 class="panel-title">PRESAGIO DE LA LUNA</h2>
                <p class="omen-title"><?= e($omen['title']) ?></p>
                <div class="editorial-text omen-body">
                    <?php $renderProse($omen['body']); ?>
                </div>
            </section>
        </div>

        <section class="panel panel-peach mt-4 chronicle-panel">
            <h2 class="panel-title">CRÓNICA DEL ARCHIVE</h2>
            <div class="chronicle editorial-text">
                <?php $renderProse($profile['chronicle']); ?>
            </div>
        </section>
    </div>
</div>
