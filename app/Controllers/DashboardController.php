<?php

declare(strict_types=1);

final class DashboardController
{
    public static function index(): void
    {
        view('dashboard/index', [
            'title' => 'Dashboard',
            'stats' => StatsService::dashboard(),
        ]);
    }
}
