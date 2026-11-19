<?php

use App\Models\Resource;
use App\Services\PdfTextExtractor;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Full-text search: read the text inside PDFs that were uploaded before this feature existed.
 * Run once:  php artisan resources:index-text        (add --all to re-read every PDF)
 */
Artisan::command('resources:index-text {--all : Re-read every PDF, not only those without text}', function (PdfTextExtractor $extractor) {
    $query = Resource::whereNotNull('file_path');
    if (! $this->option('all')) {
        $query->whereNull('content_text');
    }

    $done = 0;
    $empty = 0;
    foreach ($query->cursor() as $resource) {
        $path = Storage::disk('local')->path($resource->file_path);
        if (! is_file($path)) {
            $this->warn("Missing file: {$resource->title}");
            continue;
        }
        $text = $extractor->extract($path);
        $resource->forceFill(['content_text' => $text])->saveQuietly();
        $text ? $done++ : $empty++;
        $this->line(($text ? '  ✓ ' : '  – no text: ').$resource->title);
    }

    $this->info("Indexed {$done} PDF(s). {$empty} had no readable text.");
})->purpose('Read the text inside uploaded PDFs so they can be found by search');
