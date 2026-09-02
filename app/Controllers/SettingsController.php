<?php

declare(strict_types=1);

final class SettingsController
{
    public static function index(): void
    {
        $pdo = Database::pdo();
        $rows = $pdo->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        view('settings/index', [
            'title' => 'Configuración',
            'settings' => $settings,
            'bookCount' => BookService::countAll(),
            'version' => app_config('version'),
            'enrichPending' => GoodreadsService::countNeedingEnrichment(),
        ]);
    }

    public static function save(): void
    {
        require_csrf();
        $pdo = Database::pdo();
        $keys = ['library_name', 'default_language', 'currency'];
        foreach ($keys as $key) {
            $value = null_if_blank($_POST[$key] ?? null);
            $pdo->prepare(
                'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            )->execute([$key, $value]);
        }
        flash('success', 'Configuración guardada.');
        redirect('/configuracion');
    }
}
