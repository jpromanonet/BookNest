<div class="window">
    <div class="window-title"><span><?= icon('statistics') ?> STATISTICS</span></div>
    <div class="window-body">
        <section class="stat-grid stat-grid-hero">
            <div class="stat-block tint-lavender"><div class="stat-value"><?= format_number($stats['books']) ?></div><div class="stat-label">BOOKS</div></div>
            <div class="stat-block tint-peach"><div class="stat-value"><?= format_number($stats['pages']) ?></div><div class="stat-label">PAGES</div></div>
            <div class="stat-block tint-gold"><div class="stat-value"><?= format_number($stats['authors']) ?></div><div class="stat-label">AUTHORS</div></div>
            <div class="stat-block tint-sage"><div class="stat-value"><?= format_number($stats['read']) ?></div><div class="stat-label">READ</div></div>
            <div class="stat-block tint-parchment"><div class="stat-value"><?= format_number($stats['unread']) ?></div><div class="stat-label">UNREAD</div></div>
            <div class="stat-block tint-blue"><div class="stat-value"><?= format_number($stats['reading']) ?></div><div class="stat-label">READING</div></div>
            <div class="stat-block tint-sage"><div class="stat-value"><?= format_number($stats['pages_read']) ?></div><div class="stat-label">PAGES READ</div></div>
            <div class="stat-block tint-rose"><div class="stat-value"><?= format_number($stats['pages_pending']) ?></div><div class="stat-label">PAGES PENDING</div></div>
            <div class="stat-block tint-blue"><div class="stat-value"><?= format_number($stats['avg_pages']) ?></div><div class="stat-label">AVG PAGES</div></div>
            <div class="stat-block tint-gold"><div class="stat-value"><?= format_money($stats['estimated_value']) ?></div><div class="stat-label">EST. VALUE</div></div>
            <div class="stat-block tint-peach"><div class="stat-value"><?= format_money($stats['avg_price']) ?></div><div class="stat-label">AVG PAID</div></div>
            <div class="stat-block tint-rose"><div class="stat-value"><?= format_number($stats['without_isbn']) ?></div><div class="stat-label">NO ISBN</div></div>
        </section>

        <div class="charts-grid mt-4">
            <section class="panel panel-lavender">
                <h2 class="panel-title">STATUS · PIE</h2>
                <div class="chart-wrap">
                    <canvas id="stats-status-pie"
                            data-chart='<?= e(json_encode([
                                'type' => 'pie',
                                'labels' => array_map(static fn ($r) => status_label((string) $r['label']), $stats['by_status']),
                                'values' => array_map('intval', array_column($stats['by_status'], 'total')),
                            ], JSON_UNESCAPED_UNICODE)) ?>'></canvas>
                </div>
            </section>

            <section class="panel panel-blue">
                <h2 class="panel-title">LANGUAGES · DOUGHNUT</h2>
                <div class="chart-wrap">
                    <canvas id="stats-lang-doughnut"
                            data-chart='<?= e(json_encode([
                                'type' => 'doughnut',
                                'labels' => array_column($stats['by_language'], 'label'),
                                'values' => array_map('intval', array_column($stats['by_language'], 'total')),
                            ], JSON_UNESCAPED_UNICODE)) ?>'></canvas>
                </div>
            </section>

            <section class="panel panel-sage">
                <h2 class="panel-title">GENRES · BAR</h2>
                <div class="chart-wrap">
                    <canvas id="stats-genre-bar"
                            data-chart='<?= e(json_encode([
                                'type' => 'bar',
                                'labels' => array_column($stats['by_genre'], 'label'),
                                'values' => array_map('intval', array_column($stats['by_genre'], 'total')),
                            ], JSON_UNESCAPED_UNICODE)) ?>'></canvas>
                </div>
            </section>

            <section class="panel panel-peach">
                <h2 class="panel-title">TOP AUTHORS · BAR</h2>
                <div class="chart-wrap">
                    <canvas id="stats-authors-bar"
                            data-chart='<?= e(json_encode([
                                'type' => 'bar',
                                'labels' => array_column($stats['top_authors'], 'label'),
                                'values' => array_map('intval', array_column($stats['top_authors'], 'total')),
                            ], JSON_UNESCAPED_UNICODE)) ?>'></canvas>
                </div>
            </section>

            <section class="panel panel-gold">
                <h2 class="panel-title">DECADES · BAR</h2>
                <div class="chart-wrap">
                    <canvas id="stats-decade-bar"
                            data-chart='<?= e(json_encode([
                                'type' => 'bar',
                                'labels' => array_map('strval', array_column($stats['by_decade'], 'label')),
                                'values' => array_map('intval', array_column($stats['by_decade'], 'total')),
                            ], JSON_UNESCAPED_UNICODE)) ?>'></canvas>
                </div>
            </section>

            <section class="panel panel-rose">
                <h2 class="panel-title">ADDED BY YEAR · LINE</h2>
                <div class="chart-wrap">
                    <canvas id="stats-year-line"
                            data-chart='<?= e(json_encode([
                                'type' => 'line',
                                'labels' => array_map('strval', array_column($stats['by_year_added'], 'label')),
                                'values' => array_map('intval', array_column($stats['by_year_added'], 'total')),
                            ], JSON_UNESCAPED_UNICODE)) ?>'></canvas>
                </div>
            </section>

            <section class="panel panel-lavender span-2">
                <h2 class="panel-title">TOP PUBLISHERS · BAR</h2>
                <div class="chart-wrap chart-wrap-wide">
                    <canvas id="stats-publishers-bar"
                            data-chart='<?= e(json_encode([
                                'type' => 'bar',
                                'labels' => array_column($stats['top_publishers'], 'label'),
                                'values' => array_map('intval', array_column($stats['top_publishers'], 'total')),
                            ], JSON_UNESCAPED_UNICODE)) ?>'></canvas>
                </div>
            </section>
        </div>
    </div>
</div>
