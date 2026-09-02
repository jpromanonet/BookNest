<?php

declare(strict_types=1);

final class ImportExportController
{
    public static function index(): void
    {
        view('import_export/index', [
            'title' => 'Importar / Exportar',
            'bookCount' => BookService::countAll(),
        ]);
    }

    public static function exportJson(): void
    {
        $data = ImportExportService::exportJson();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="booknest-export-' . date('Ymd-His') . '.json"');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function exportCsv(): void
    {
        $csv = ImportExportService::exportCsv();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="booknest-export-' . date('Ymd-His') . '.csv"');
        echo $csv;
        exit;
    }

    public static function import(): void
    {
        require_csrf();
        $replace = isset($_POST['replace']);
        try {
            if (!empty($_FILES['file']['tmp_name'])) {
                $name = strtolower((string) ($_FILES['file']['name'] ?? ''));
                $content = (string) file_get_contents($_FILES['file']['tmp_name']);
                if (str_ends_with($name, '.csv')) {
                    $result = ImportExportService::importCsv($content, $replace);
                } else {
                    $result = ImportExportService::importJson($content, $replace);
                }
                flash('success', $result['imported'] . ' volumes added to the archive.');
            } else {
                flash('error', 'Seleccioná un archivo JSON o CSV.');
            }
        } catch (Throwable $e) {
            flash('error', $e->getMessage());
        }
        redirect('/importar-exportar');
    }
}
