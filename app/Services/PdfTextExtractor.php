<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Reads the text inside a PDF so it can be searched.
 * Works for normal (digital) PDFs. Scanned PDFs are only pictures of pages,
 * so they have no readable text (that would need OCR, which is outside the scope).
 */
class PdfTextExtractor
{
    /** Maximum characters kept per file, so very large books stay fast to search. */
    private const MAX_CHARS = 300000;

    public function extract(string $absolutePath): ?string
    {
        try {
            $text = (new Parser)->parseFile($absolutePath)->getText();
        } catch (Throwable $e) {
            Log::warning('PDF text extraction failed', ['file' => $absolutePath, 'error' => $e->getMessage()]);

            return null;
        }

        // Tidy up: one space between words, no control characters
        $text = preg_replace('/[^\P{C}\n]+/u', ' ', $text) ?? '';
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return $text === '' ? null : mb_substr($text, 0, self::MAX_CHARS);
    }
}
