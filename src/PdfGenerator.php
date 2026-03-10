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
    public function loadHtml(string $html): Dompdf
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', $this->config['default_font'] ?? 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper(
            $this->config['paper'] ?? 'A4',
            $this->config['orientation'] ?? 'portrait'
        );
        $dompdf->render();
        return $dompdf;
    }

    /**
     * Load Blade view with data and return Dompdf instance.
     */
    public function loadView(string $viewName, array $data = []): Dompdf
    {
        $html = $this->view->make($viewName, $data)->render();
        return $this->loadHtml($html);
    }

    /**
     * Generate PDF from HTML and return raw output string.
     */
    public function fromHtml(string $html): string
    {
        return $this->loadHtml($html)->output();
    }

    /**
     * Generate PDF from Blade view and return raw output string.
     */
    public function fromView(string $viewName, array $data = []): string
    {
        return $this->loadView($viewName, $data)->output();
    }

    /**
     * Generate from view and stream in browser (inline).
     */
    public function stream(string $viewName, array $data = [], string $filename = 'document.pdf'): \Illuminate\Http\Response
    {
        $dompdf = $this->loadView($viewName, $data);
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate from view and trigger download.
     */
    public function download(string $viewName, array $data = [], string $filename = 'document.pdf'): \Illuminate\Http\Response
    {
        $dompdf = $this->loadView($viewName, $data);
        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate from view and save to path. Returns path.
     */
    public function save(string $viewName, string $path, array $data = []): string
    {
        $output = $this->fromView($viewName, $data);
        file_put_contents($path, $output);
        return $path;
    }

    /**
     * Generate from HTML and save to path.
     */
    public function saveHtml(string $html, string $path): string
    {
        $output = $this->fromHtml($html);
        file_put_contents($path, $output);
        return $path;
    }
}
