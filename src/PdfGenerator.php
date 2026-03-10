<?php

namespace Sabuj073\PdfGenerator;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;

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
     * Load HTML string and return Dompdf instance (call ->stream(), ->output() or get raw).
     */
    public function loadHtml(string $html, bool $useBanglaFont = null): Dompdf
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $bangla = $this->config['bangla_font'] ?? [];
        $useBangla = $useBanglaFont ?? ($bangla['enabled'] ?? false);
        $fontPath = $bangla['path'] ?? $this->resolveBanglaFontPath();
        $fontFamily = $bangla['family'] ?? 'Noto Sans Bengali';

        if ($useBangla && $fontPath && is_file($fontPath)) {
            $fontDirReal = realpath(dirname($fontPath));
            if ($fontDirReal !== false) {
                $options->setFontDir($fontDirReal);
                $options->setFontCache($fontDirReal);
                $chroot = $options->getChroot();
                $chroot[] = $fontDirReal;
                $options->setChroot($chroot);
            }
            $options->set('defaultFont', $fontFamily);
            $html = $this->injectBanglaFontCss($html, $fontPath, $fontFamily);
        } else {
            $options->set('defaultFont', $this->config['default_font'] ?? 'DejaVu Sans');
        }

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper(
            $this->config['paper'] ?? 'A4',
            $this->config['orientation'] ?? 'portrait'
        );
        $dompdf->render();
        return $dompdf;
    }

    /**
     * Inject @font-face for Bangla so Bengali text renders in PDF.
     * Dompdf requires chroot + fontDir and a file:// base URL to load the font.
     */
    protected function injectBanglaFontCss(string $html, string $fontPath, string $fontFamily): string
    {
        $fontDirReal = realpath(dirname($fontPath));
        $fontFile = basename($fontPath);
        $baseHref = 'file:///' . str_replace(['\\', ' '], ['/', '%20'], $fontDirReal) . '/';
        $css = sprintf(
            '<style>@font-face{font-family:"%s";src:url("%s") format("truetype");font-weight:400;font-style:normal;}body,body *{font-family:"%s",DejaVu Sans,sans-serif !important;}</style>',
            $fontFamily,
            $fontFile,
            $fontFamily
        );
        $inject = '<meta charset="UTF-8"><base href="' . htmlspecialchars($baseHref) . '">' . $css;
        if (stripos($html, '<head>') !== false) {
            return preg_replace('/<head\s*>/i', '<head>' . $inject, $html, 1);
        }
        if (stripos($html, '<html') !== false) {
            return preg_replace('/(<html[^>]*>)/i', '$1<head>' . $inject . '</head>', $html, 1);
        }
        return '<!DOCTYPE html><html><head>' . $inject . '</head><body>' . $html . '</body></html>';
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
     * Load Blade view with data and return Dompdf instance.
     * Set $useBanglaFont = true when view has Bangla text.
     */
    public function loadView(string $viewName, array $data = [], bool $useBanglaFont = null): Dompdf
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
