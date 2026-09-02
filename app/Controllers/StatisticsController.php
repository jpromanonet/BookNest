<?php

declare(strict_types=1);

final class StatisticsController
{
    public static function index(): void
    {
        view('statistics/index', [
            'title' => 'Estadísticas',
            'stats' => StatsService::dashboard(),
        ]);
    }
}
