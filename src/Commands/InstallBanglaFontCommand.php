<?php

namespace Sabuj073\PdfGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class InstallBanglaFontCommand extends Command
{
    protected $signature = 'pdf-generator:install-bangla-font
                            {--path= : Custom directory to save the font}';
    protected $description = 'Download Noto Sans Bengali font (supports Bangla + English) for PDF generation';

    protected array $sources = [
        ['url' => 'https://github.com/notofonts/bengali/releases/download/v3.011/NotoSansBengali-v3.011.zip', 'zip' => true],
        ['url' => 'https://github.com/google/fonts/archive/refs/heads/main.zip', 'zip' => true, 'path' => 'fonts-main/ofl/notosansbengali/'],
    ];

    public function handle(): int
    {
        $targetDir = $this->option('path') ?: storage_path('fonts');
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                $this->error("Could not create directory: {$targetDir}");
                return 1;
            }
        }

        $targetPath = rtrim($targetDir, '/') . '/NotoSansBengali-Regular.ttf';

        $this->info('Downloading Noto Sans Bengali font (Bangla + English support)...');

        $found = false;

        foreach ($this->sources as $source) {
            $url = $source['url'] ?? $source;
            try {
                $response = Http::timeout(90)->withOptions(['allow_redirects' => true])->get($url);
                if (!$response->successful()) {
                    continue;
                }
                $bytes = $response->body();
                if (strlen($bytes) < 1000) {
                    continue;
                }
                if (str_starts_with($bytes, 'PK')) {
                    $fontBytes = $this->extractTtfFromZip($bytes, $source['path'] ?? null);
                    if ($fontBytes) {
                        file_put_contents($targetPath, $fontBytes);
                        $found = true;
                        break;
                    }
                }
                if (preg_match('/^[\x00-\x08]/', substr($bytes, 0, 1))) {
                    file_put_contents($targetPath, $bytes);
                    $found = true;
                    break;
                }
            } catch (\Throwable $e) {
                $this->warn("Source failed: " . $e->getMessage());
            }
        }

        if (!$found) {
            $this->error('Automatic download failed. Please download manually:');
            $this->line('1. Go to https://fonts.google.com/noto/specimen/Noto+Sans+Bengali');
            $this->line('2. Download the font and extract NotoSansBengali-Regular.ttf');
            $this->line("3. Copy to: {$targetPath}");
            return 1;
        }

        $this->info("Font installed: {$targetPath}");
        $this->line('Add to .env: PDF_BANGLA_FONT_PATH=' . $targetPath);
        return 0;
    }

    protected function extractTtfFromZip(string $zipBytes, ?string $prefix = null): ?string
    {
        $tmp = sys_get_temp_dir() . '/pdf-font-' . uniqid() . '.zip';
        file_put_contents($tmp, $zipBytes);
        $zip = new \ZipArchive();
        if (!$zip->open($tmp)) {
            @unlink($tmp);
            return null;
        }
        $best = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($prefix && !str_starts_with($name, $prefix)) {
                continue;
            }
            if (preg_match('/NotoSansBengali[^/]*Regular[^/]*\.ttf$/i', $name)) {
                $content = $zip->getFromIndex($i);
                $zip->close();
                @unlink($tmp);
                return $content ?: null;
            }
            if (preg_match('/NotoSansBengali[^/]*\.ttf$/i', $name) || preg_match('/notosansbengali[^/]*\.ttf$/i', $name)) {
                $best = $zip->getFromIndex($i);
            }
        }
        $zip->close();
        @unlink($tmp);
        return $best ?: null;
    }
}
