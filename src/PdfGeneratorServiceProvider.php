<?php

namespace Sabuj073\PdfGenerator;

use Illuminate\Support\ServiceProvider;

class PdfGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/pdf-generator.php', 'pdf-generator');

        $this->app->singleton(PdfGenerator::class, function ($app) {
            return new PdfGenerator(
                $app['view'],
                config('pdf-generator')
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/pdf-generator.php' => config_path('pdf-generator.php'),
            ], 'pdf-generator-config');
        }
    }
}
