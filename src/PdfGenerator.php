<?php

namespace Sabuj073\PdfGenerator;

use Dompdf\Dompdf;
use Dompdf\Options;
use Mpdf\Mpdf;

class PdfGenerator
{
    protected $view;
    protected array $config;

    public function __construct($view, array $config)
    {
        $this->view = $view;
        $this->config = $config;
    }

    /**
     * Load HTML and return an object with ->output() (Dompdf or mPDF wrapper).
     * When useBanglaFont is true, uses mPDF for proper Bengali rendering (Dompdf does not support it).
     */
    public function loadHtml(string $html, bool $useBanglaFont = null)
    {
        $bangla = $this->config['bangla_font'] ?? [];
        $useBangla = $useBanglaFont ?? ($bangla['enabled'] ?? false);
        $fontPath = $bangla['path'] ?? $this->resolveBanglaFontPath();

        if ($useBangla && $fontPath && is_file($fontPath)) {
            return $this->renderWithMpdf($html, $fontPath);
        }

        return $this->renderWithDompdf($html);
    }

    /**
     * Bengali PDF via mPDF (supports complex script / Bangla). Dompdf does not.
     */
    protected function renderWithMpdf(string $html, string $fontPath): object
    {
        $fontDir = realpath(dirname($fontPath));
        $fontFile = basename($fontPath);
        $fontKey = 'notosansbengali';

        $defaultFontDirs = (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'] ?? [];
        $defaultFontData = (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'] ?? [];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $this->config['paper'] ?? 'A4',
            'orientation' => $this->config['orientation'] ?? 'portrait',
            'default_font_size' => 11,
            'default_font' => $fontKey,
            'fontDir' => array_merge($defaultFontDirs, [$fontDir]),
            'fontdata' => $defaultFontData + [
                $fontKey => [
                    'R' => $fontFile,
                    'I' => $fontFile,
                    'B' => $fontFile,
                    'BI' => $fontFile,
                ],
            ],
        ]);

        $html = $this->ensureUtf8Head($html);
        $html = $this->injectMpdfBanglaStyle($html);
        $mpdf->WriteHTML($html);
        $output = $mpdf->Output('', 'S');

        return new class($output) {
            private string $out;
            public function __construct(string $out) { $this->out = $out; }
            public function output(): string { return $this->out; }
        };
    }

    protected function renderWithDompdf(string $html): Dompdf
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', $this->config['default_font'] ?? 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->ensureUtf8Head($html), 'UTF-8');
        $dompdf->setPaper(
            $this->config['paper'] ?? 'A4',
            $this->config['orientation'] ?? 'portrait'
        );
        $dompdf->render();
        return $dompdf;
    }

    protected function injectMpdfBanglaStyle(string $html): string
    {
        $style = '<style>body,body *{font-family:notosansbengali,sans-serif;}</style>';
        if (stripos($html, '<head>') !== false) {
            return preg_replace('/<head\s*>/i', '<head>' . $style, $html, 1);
        }
        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head\s*>/i', $style . '</head>', $html, 1);
        }
        return $style . $html;
    }

    protected function ensureUtf8Head(string $html): string
    {
        if (stripos($html, 'charset') !== false && stripos($html, 'utf-8') !== false) {
            return $html;
        }
        if (stripos($html, '<head>') !== false) {
            return preg_replace('/<head\s*>/i', '<head><meta charset="UTF-8">', $html, 1);
        }
        if (stripos($html, '<html') !== false) {
            return preg_replace('/(<html[^>]*>)/i', '$1<head><meta charset="UTF-8"></head>', $html, 1);
        }
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';
    }

    protected function resolveBanglaFontPath(): ?string
    {
        $packageFont = __DIR__ . '/../resources/fonts/NotoSansBengali-Regular.ttf';
        if (is_file($packageFont)) {
            return $packageFont;
        }
        $storageFont = storage_path('fonts/NotoSansBengali-Regular.ttf');
        return is_file($storageFont) ? $storageFont : null;
    }

    /**
     * Load Blade view with data. Returns object with ->output() (Dompdf or mPDF wrapper).
     * Set $useBanglaFont = true when view has Bangla text.
     */
    public function loadView(string $viewName, array $data = [], bool $useBanglaFont = null): object
    {
        $html = $this->view->make($viewName, $data)->render();
        return $this->loadHtml($html, $useBanglaFont);
    }

    /**
     * Generate PDF from HTML and return raw output string.
     * Pass true as second argument for Bangla font support.
     */
    public function fromHtml(string $html, bool $useBanglaFont = null): string
    {
        return $this->loadHtml($html, $useBanglaFont)->output();
    }

    /**
     * Generate PDF from Blade view and return raw output string.
     * Pass true as third argument for Bangla font support.
     */
    public function fromView(string $viewName, array $data = [], bool $useBanglaFont = null): string
    {
        return $this->loadView($viewName, $data, $useBanglaFont)->output();
    }

    /**
     * Generate from view and stream in browser (inline).
     * Pass true as fourth argument for Bangla font.
     */
    public function stream(string $viewName, array $data = [], string $filename = 'document.pdf', bool $useBanglaFont = null): \Illuminate\Http\Response
    {
        $dompdf = $this->loadView($viewName, $data, $useBanglaFont);
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate from view and trigger download.
     * Pass true as fourth argument for Bangla font.
     */
    public function download(string $viewName, array $data = [], string $filename = 'document.pdf', bool $useBanglaFont = null): \Illuminate\Http\Response
    {
        $dompdf = $this->loadView($viewName, $data, $useBanglaFont);
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate from view and save to path. Returns path.
     * Pass true as fourth argument for Bangla font.
     */
    public function save(string $viewName, string $path, array $data = [], bool $useBanglaFont = null): string
    {
        $output = $this->fromView($viewName, $data, $useBanglaFont);
        file_put_contents($path, $output);
        return $path;
    }

    /**
     * Generate from HTML and save to path.
     * Pass true as third argument for Bangla font.
     */
    public function saveHtml(string $html, string $path, bool $useBanglaFont = null): string
    {
        $output = $this->fromHtml($html, $useBanglaFont);
        file_put_contents($path, $output);
        return $path;
    }
}
