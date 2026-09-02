<?php

declare(strict_types=1);

final class ProfileController
{
    public static function index(): void
    {
        $stats = StatsService::dashboard();
        view('profile/index', [
            'title' => 'Perfil lector',
            'stats' => $stats,
            'profile' => ReaderProfileService::build($stats),
        ]);
    }
}
