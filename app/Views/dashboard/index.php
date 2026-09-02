<div class="window">
    <div class="window-title">
        <span><?= icon('dashboard') ?> DASHBOARD</span>
        <a class="btn btn-primary" href="<?= e(url('/biblioteca/nuevo')) ?>"><?= icon('add') ?> ADD BOOK</a>
    </div>
    <div class="window-body">
        <section class="stat-grid stat-grid-hero">
            <div class="stat-block tint-lavender">
                <div class="stat-value"><?= format_number($stats['books']) ?></div>
                <div class="stat-label">BOOKS</div>
            </div>
            <div class="stat-block tint-peach">
                <div class="stat-value"><?= format_number($stats['pages']) ?></div>
                <div class="stat-label">PAGES</div>
            </div>
            <div class="stat-block tint-gold">
                <div class="stat-value"><?= format_number($stats['authors']) ?></div>
                <div class="stat-label">AUTHORS</div>
            </div>
            <div class="stat-block tint-sage">
                <div class="stat-value"><?= format_number($stats['read']) ?></div>
                <div class="stat-label">READ</div>
                <div class="stat-sub"><?= (int) $stats['pct_read'] ?>%</div>
            </div>
            <div class="stat-block tint-parchment">
                <div class="stat-value"><?= format_number($stats['unread']) ?></div>
                <div class="stat-label">UNREAD</div>
                <div class="stat-sub"><?= (int) $stats['pct_unread'] ?>%</div>
            </div>
            <div class="stat-block tint-blue">
                <div class="stat-value"><?= format_number($stats['reading']) ?></div>
                <div class="stat-label">READING</div>
                <div class="stat-sub"><?= (int) $stats['pct_reading'] ?>%</div>
            </div>
            <div class="stat-block tint-gold">
                <div class="stat-value"><?= format_money($stats['estimated_value']) ?></div>
                <div class="stat-label">EST. VALUE</div>
            </div>
            <div class="stat-block tint-rose">
                <div class="stat-value"><?= format_number($stats['collections'] + $stats['series']) ?></div>
                <div class="stat-label">COLLECTIONS</div>
            </div>
        </section>

        <section class="panel panel-sage mt-4">
            <h2 class="panel-title">READING PROGRESS</h2>
            <div class="progress-bar progress-bar-lg" aria-label="<?= (int) $stats['progress'] ?>%">
                <?php for ($i = 0; $i < 24; $i++): ?>
                    <span class="progress-block <?= $i < (int) round($stats['progress'] / (100 / 24)) ? 'is-on' : '' ?>"></span>
                <?php endfor; ?>
                <span class="progress-pct"><?= (int) $stats['progress'] ?>%</span>
            </div>
            <p class="muted mt-2">
                <?= (int) $stats['progress'] ?>% del archive recorrido ·
                <?= (int) $stats['pct_read'] ?>% leídos ·
                <?= (int) $stats['pct_unread'] ?>% pendientes ·
                <?= format_number($stats['pages_read']) ?> páginas leídas
            </p>
            <p class="mt-3">
                <a class="btn btn-magic" href="<?= e(url('/perfil')) ?>"><?= icon('profile') ?> VER PERFIL LECTOR</a>
            </p>
        </section>

        <div class="split mt-4">
            <section class="panel panel-lavender">
                <h2 class="panel-title">BY GENRE</h2>
                <div class="chart-wrap">
                    <canvas id="dash-genre-chart" height="220"
                            data-chart='<?= e(json_encode([
                                'labels' => array_column($stats['by_genre'], 'label'),
                                'values' => array_map('intval', array_column($stats['by_genre'], 'total')),
                            ], JSON_UNESCAPED_UNICODE)) ?>'></canvas>
                </div>
            </section>

            <section class="panel panel-blue">
                <h2 class="panel-title">BY LANGUAGE</h2>
                <div class="chart-wrap">
                    <canvas id="dash-lang-chart" height="220"
                            data-chart='<?= e(json_encode([
                                'labels' => array_column($stats['by_language'], 'label'),
                                'values' => array_map('intval', array_column($stats['by_language'], 'total')),
                            ], JSON_UNESCAPED_UNICODE)) ?>'></canvas>
                </div>
            </section>
        </div>

        <section class="panel panel-peach mt-4">
            <h2 class="panel-title">READING STATUS</h2>
            <div class="chart-wrap chart-wrap-wide">
                <canvas id="dash-status-chart" height="160"
                        data-chart='<?= e(json_encode([
                            'labels' => array_map(static fn ($r) => status_label((string) $r['label']), $stats['by_status']),
                            'values' => array_map('intval', array_column($stats['by_status'], 'total')),
                        ], JSON_UNESCAPED_UNICODE)) ?>'></canvas>
            </div>
        </section>
    </div>
</div>
