<?php

declare(strict_types=1);

/**
 * Minimal multi-page PDF writer for BookNest library exports.
 * Uses core Helvetica + Windows-1252 for Spanish accents.
 */
final class SimpleListPdf
{
    private const PAGE_W = 841.89; // A4 landscape
    private const PAGE_H = 595.28;
    private const MARGIN_L = 36.0;
    private const MARGIN_R = 36.0;
    private const MARGIN_T = 42.0;
    private const MARGIN_B = 36.0;

    /** @var list<string> */
    private array $pages = [];
    private string $buf = '';
    private float $y;
    private int $row = 0;

    public function __construct()
    {
        $this->y = self::PAGE_H - self::MARGIN_T;
        $this->writeHeader();
    }

    public function title(string $text): void
    {
        $this->setFont(14, true);
        $this->text(self::MARGIN_L, $this->y, $text);
        $this->y -= 18;
        $this->setFont(9, false);
    }

    public function subtitle(string $text): void
    {
        $this->text(self::MARGIN_L, $this->y, $text);
        $this->y -= 14;
    }

    public function blank(float $dy = 8.0): void
    {
        $this->y -= $dy;
    }

    /**
     * @param list<string> $cols
     */
    public function headerRow(array $cols, array $widths): void
    {
        $this->ensureSpace(16);
        $this->setFont(8, true);
        $this->drawRow($cols, $widths, true);
        $this->setFont(8, false);
    }

    /**
     * @param list<string> $cols
     */
    public function dataRow(array $cols, array $widths): void
    {
        $this->ensureSpace(14);
        $this->drawRow($cols, $widths, false);
        $this->row++;
    }

    public function output(string $filename): never
    {
        $this->flushPage();
        $pdf = $this->build();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }

    private function writeHeader(): void
    {
        $this->setFont(11, true);
        $this->text(self::MARGIN_L, $this->y, 'BOOKNEST — LIBRARY EXPORT');
        $this->setFont(8, false);
        $this->text(self::PAGE_W - self::MARGIN_R - 120, $this->y, date('Y-m-d H:i'));
        $this->y -= 12;
        $this->line(self::MARGIN_L, $this->y, self::PAGE_W - self::MARGIN_R, $this->y);
        $this->y -= 14;
    }

    private function writeFooter(int $pageNum, int $pageCount): string
    {
        $ops = '';
        $ops .= "BT /F1 8 Tf 1 0 0 1 " . self::n(self::MARGIN_L) . ' ' . self::n(22) . " Tm (" . self::esc('BookNest personal library') . ") Tj ET\n";
        $ops .= "BT /F1 8 Tf 1 0 0 1 " . self::n(self::PAGE_W - self::MARGIN_R - 60) . ' ' . self::n(22) . " Tm (" . self::esc("Page {$pageNum} / {$pageCount}") . ") Tj ET\n";
        return $ops;
    }

    /** @param list<string> $cols */
    private function drawRow(array $cols, array $widths, bool $header): void
    {
        $x = self::MARGIN_L;
        if ($header || $this->row % 2 === 1) {
            $fill = $header ? '0.85 0.80 0.90 rg' : '0.96 0.93 0.88 rg';
            $this->buf .= "q {$fill} " . self::n($x) . ' ' . self::n($this->y - 3) . ' '
                . self::n(array_sum($widths)) . " 12 re f Q\n";
        }
        foreach ($cols as $i => $col) {
            $w = $widths[$i] ?? 80;
            $text = $this->clip($col, (int) max(4, floor($w / 4.6)));
            $this->text($x + 2, $this->y, $text);
            $x += $w;
        }
        $this->y -= 13;
    }

    private function ensureSpace(float $needed): void
    {
        if ($this->y - $needed < self::MARGIN_B) {
            $this->newPage();
        }
    }

    private function newPage(): void
    {
        $this->flushPage();
        $this->y = self::PAGE_H - self::MARGIN_T;
        $this->row = 0;
        $this->writeHeader();
    }

    private function flushPage(): void
    {
        if ($this->buf === '' && $this->pages === []) {
            return;
        }
        $this->pages[] = $this->buf;
        $this->buf = '';
    }

    private function setFont(float $size, bool $bold): void
    {
        $font = $bold ? 'F2' : 'F1';
        $this->buf .= "BT /{$font} " . self::n($size) . " Tf ET\n";
    }

    private function text(float $x, float $y, string $text): void
    {
        $this->buf .= 'BT 1 0 0 1 ' . self::n($x) . ' ' . self::n($y) . ' Tm (' . self::esc($text) . ") Tj ET\n";
    }

    private function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->buf .= "0.4 0.35 0.4 RG 0.8 w " . self::n($x1) . ' ' . self::n($y1) . ' m '
            . self::n($x2) . ' ' . self::n($y2) . " l S\n";
    }

    private function build(): string
    {
        if ($this->buf !== '') {
            $this->flushPage();
        }
        if ($this->pages === []) {
            $this->pages[] = '';
        }

        $pageCount = count($this->pages);
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $kids[] = (3 + $i * 2) . ' 0 R';
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $pageCount . ' >>';

        $contentIds = [];
        for ($i = 0; $i < $pageCount; $i++) {
            $pageObj = 3 + $i * 2;
            $contentObj = 4 + $i * 2;
            $contentIds[] = $contentObj;
            $objects[$pageObj] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %s %s] /Resources << /Font << /F1 %d 0 R /F2 %d 0 R >> >> /Contents %d 0 R >>',
                self::n(self::PAGE_W),
                self::n(self::PAGE_H),
                3 + $pageCount * 2,
                4 + $pageCount * 2,
                $contentObj
            );
            $stream = $this->pages[$i] . $this->writeFooter($i + 1, $pageCount);
            $objects[$contentObj] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }

        $fontRegular = 3 + $pageCount * 2;
        $fontBold = 4 + $pageCount * 2;
        $objects[$fontRegular] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[$fontBold] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xrefPos = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxId; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefPos}\n%%EOF";
        return $pdf;
    }

    private function clip(string $text, int $maxChars): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $maxChars) {
            return mb_substr($text, 0, max(1, $maxChars - 1), 'UTF-8') . '…';
        }
        if (strlen($text) > $maxChars) {
            return substr($text, 0, max(1, $maxChars - 1)) . '~';
        }
        return $text;
    }

    private static function esc(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        if ($converted === false) {
            $converted = utf8_decode($text); // fallback
        }
        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\\(', '\\)', ' ', ' '],
            $converted
        );
    }

    private static function n(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') ?: '0';
    }
}
