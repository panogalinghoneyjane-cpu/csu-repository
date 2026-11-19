<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    /** A resource is "outdated" when it is older than this many years. */
    public const OUTDATED_AFTER_YEARS = 5;

    public const TYPES = ['Ebook', 'Journal', 'Research Paper', 'Thesis', 'Module', 'Other'];

    protected $fillable = [
        'subject_id', 'uploaded_by', 'recommendation_id', 'title', 'authors', 'publication_year',
        'resource_type', 'academic_year', 'description', 'keywords', 'file_path', 'external_url',
        'content_text', 'page_count', 'status', 'has_physical_copy', 'call_number', 'shelf_location',
    ];

    protected $casts = ['has_physical_copy' => 'boolean'];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function recommendation()
    {
        return $this->belongsTo(Recommendation::class);
    }

    public function accessLogs()
    {
        return $this->hasMany(AccessLog::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOutdated(Builder $query): Builder
    {
        return $query->where('publication_year', '<', now()->year - self::OUTDATED_AFTER_YEARS);
    }

    /** Words from the user's search, without filler words like "the" and "and". */
    public static function searchWords(string $term): array
    {
        $stop = ['the', 'and', 'for', 'with', 'from', 'into', 'of', 'in', 'to', 'a', 'an', 'on', 'by', '&', '-', ':'];
        $words = array_map(fn ($w) => trim($w, ':,.;()"\''), preg_split('/\s+/', trim($term)));
        $words = array_values(array_filter($words, fn ($w) => mb_strlen($w) >= 3 && ! in_array(mb_strtolower($w), $stop, true)));

        return $words ?: [trim($term)];
    }

    /**
     * Splits a search into [words, years]. A year is a 4-digit number like 2023;
     * it only matches resources published in that exact year.
     */
    public static function splitSearch(string $term): array
    {
        $words = [];
        $years = [];
        foreach (self::searchWords($term) as $w) {
            if (preg_match('/^(19|20)\d{2}$/', $w)) {
                $years[] = (int) $w;
            } else {
                $words[] = $w;
            }
        }

        return [$words, $years];
    }

    /**
     * Search a resource's own details: title, authors, keywords, description, type, link,
     * physical copy details AND the text inside the uploaded PDF. Not case-sensitive.
     * EVERY word must be found (so "information security" does not show everything with "information").
     * Program and college names are never searched, because they made almost everything match.
     * $withCourse = also match the course code/name (used by the librarian's lists, not the main Search page,
     * where matching courses are shown in their own box instead).
     */
    public function scopeSearch(Builder $query, string $term, bool $withCourse = true): Builder
    {
        [$words, $years] = self::splitSearch($term);

        if ($years) {
            // Only the publication year (not the school year "2025-2026", which almost every resource has)
            $query->whereIn('publication_year', $years);
        }

        foreach ($words as $word) {
            $like = '%'.$word.'%';
            $query->where(fn (Builder $q) => $q->whereLike('title', $like)
                ->orWhereLike('authors', $like)
                ->orWhereLike('keywords', $like)
                ->orWhereLike('description', $like)
                ->orWhereLike('content_text', $like)          // text inside the PDF
                ->orWhereLike('external_url', $like)
                ->orWhereLike('resource_type', $like)         // Ebook, Journal, Thesis...
                ->orWhereLike('call_number', $like)           // physical copy
                ->orWhereLike('shelf_location', $like)
                ->when($withCourse, fn ($q) => $q->orWhereHas('subject',
                    fn ($s) => $s->where(fn ($w) => $w->whereLike('name', $like)->orWhereLike('code', $like)))));
        }

        return $query;
    }

    /**
     * The text with every search word marked in yellow. Everything else is escaped, so it is safe to print with {!! !!}.
     */
    public static function highlight(?string $text, ?string $term): \Illuminate\Support\HtmlString
    {
        $text = (string) $text;
        $words = array_filter(self::searchWords((string) $term), fn ($w) => trim($w) !== '');
        if ($text === '' || ! $words) {
            return new \Illuminate\Support\HtmlString(e($text));
        }

        $pattern = '/('.implode('|', array_map(fn ($w) => preg_quote($w, '/'), $words)).')/iu';
        $html = '';
        foreach (preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $i => $piece) {
            $html .= $i % 2 === 1 ? '<mark class="bg-yellow-200 rounded px-0.5">'.e($piece).'</mark>' : e($piece);
        }

        return new \Illuminate\Support\HtmlString($html);
    }

    /**
     * Where the search words were found, used to group the results:
     * 1 = in the title, 2 = in the details (authors, keywords, description...), 3 = only inside the PDF.
     */
    public function matchTier(string $term): int
    {
        [$words] = self::splitSearch($term);
        $has = fn (string $text) => collect($words)->every(fn ($w) => mb_stripos($text, $w) !== false);

        if ($has((string) $this->title)) {
            return 1;
        }

        $details = implode(' ', [$this->title, $this->authors, $this->keywords, $this->description, $this->resource_type,
            $this->external_url, $this->call_number, $this->shelf_location]);

        return $has($details) ? 2 : 3;
    }

    /**
     * Relevance score for ranking search results (higher = more relevant):
     * title matches count most, then subject/keywords/authors/description,
     * then how many times the words appear inside the file.
     */
    public function relevance(string $term): int
    {
        $program = $this->subject?->program;
        $college = $program?->college;
        $score = 0;

        foreach (self::searchWords($term) as $w) {
            $w = mb_strtolower($w);
            $score += 50 * mb_substr_count(mb_strtolower($this->title), $w);
            $score += 15 * mb_substr_count(mb_strtolower($this->subject?->name.' '.$this->subject?->code), $w);
            $score += 12 * mb_substr_count(mb_strtolower($program?->name.' '.$program?->code.' '.$college?->name.' '.$college?->code), $w);
            $score += 10 * mb_substr_count(mb_strtolower($this->keywords.' '.$this->authors.' '.$this->resource_type.' '.$this->external_url), $w);
            $score += 8 * mb_substr_count(mb_strtolower($this->publication_year.' '.$this->academic_year.' '.$this->call_number.' '.$this->shelf_location), $w);
            $score += 5 * mb_substr_count(mb_strtolower((string) $this->description), $w);
            $score += min(40, mb_substr_count(mb_strtolower((string) $this->content_text), $w));
        }

        return $score;
    }

    /**
     * Google Scholar-style snippet: up to 3 short pieces of text around the places where the
     * search words appear (inside the file first, then the description), joined with "…".
     */
    public function searchSnippet(string $term, int $radius = 70, int $maxParts = 3): string
    {
        $source = trim((string) $this->content_text) !== '' ? $this->content_text : (string) $this->description;
        if ($source === '') {
            return '';
        }

        $words = self::searchWords($term);
        $hits = [];
        foreach ($words as $w) {
            $offset = 0;
            while (count($hits) < 12 && ($pos = mb_stripos($source, $w, $offset)) !== false) {
                $hits[] = $pos;
                $offset = $pos + mb_strlen($w);
            }
        }
        sort($hits);

        if (empty($hits)) {
            return mb_substr($source, 0, 2 * $radius).(mb_strlen($source) > 2 * $radius ? ' …' : '');
        }

        // Turn the hit positions into non-overlapping text pieces
        $parts = [];
        $lastEnd = -1;
        foreach ($hits as $pos) {
            if ($pos < $lastEnd) {
                continue;
            }
            $start = max(0, $pos - $radius);
            $end = min(mb_strlen($source), $pos + $radius);
            // start/end on word boundaries
            if ($start > 0 && ($sp = mb_strpos($source, ' ', $start)) !== false && $sp < $pos) {
                $start = $sp + 1;
            }
            if ($end < mb_strlen($source) && ($sp = mb_strrpos(mb_substr($source, 0, $end), ' ')) !== false && $sp > $pos) {
                $end = $sp;
            }
            $parts[] = trim(mb_substr($source, $start, $end - $start));
            $lastEnd = $end;
            if (count($parts) >= $maxParts) {
                break;
            }
        }

        return '… '.implode(' … ', $parts).' …';
    }

    /** APA-style citation, e.g. "Santos, J. (2025). Modern Web Development with Laravel. CSU Digital Library." */
    public function citation(): string
    {
        $text = "{$this->authors} ({$this->publication_year}). {$this->title}.";

        return $this->isLink() ? $text.' Retrieved from '.$this->external_url : $text.' Caraga State University Digital Library Repository.';
    }

    /**
     * Shows WHERE a search word was found when it is not already visible in the title:
     * inside the PDF text first, then the description, then the link.
     * Returns ['where' => 'file'|'description'|'link', 'text' => '…surrounding text…'] or null.
     */
    public function contentSnippet(string $term, int $radius = 90): ?array
    {
        $sources = [
            'file' => (string) $this->content_text,
            'description' => (string) $this->description,
            'link' => (string) $this->external_url,
        ];

        foreach (self::searchWords($term) as $word) {
            if (mb_stripos($this->title, $word) !== false) {
                continue;   // already obvious from the title
            }
            foreach ($sources as $where => $text) {
                $pos = $text === '' ? false : mb_stripos($text, $word);
                if ($pos === false) {
                    continue;
                }
                $start = max(0, $pos - $radius);
                $piece = trim(mb_substr($text, $start, mb_strlen($word) + 2 * $radius));
                $more = $start + mb_strlen($word) + 2 * $radius < mb_strlen($text);

                return ['where' => $where, 'text' => ($start > 0 ? '…' : '').$piece.($more ? '…' : '')];
            }
        }

        return null;
    }

    /** True when this resource is an online link instead of an uploaded PDF. */
    public function isLink(): bool
    {
        return ! empty($this->external_url);
    }

    public function isOutdated(): bool
    {
        return $this->publication_year < now()->year - self::OUTDATED_AFTER_YEARS;
    }

    /**
     * Search keywords are generated automatically from the title and the subject name,
     * so the librarian does not have to type them. They power the keyword search and the
     * "Shared topic" part of the Recommended Resources feature.
     */
    public static function autoKeywords(string $title, ?Subject $subject = null): string
    {
        $stop = ['the', 'and', 'for', 'with', 'from', 'into', 'its', 'are', 'was', 'this', 'that', 'how', 'what',
            'introduction', 'intro', 'principles', 'fundamentals', 'essentials', 'basics', 'guide', 'edition', 'volume'];

        $text = mb_strtolower($title.' '.($subject?->name ?? ''));
        $words = preg_split('/[^\pL\pN]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = array_filter($words, fn ($w) => mb_strlen($w) >= 3 && ! is_numeric($w) && ! in_array($w, $stop, true));

        return implode(', ', array_slice(array_values(array_unique($words)), 0, 12));
    }

    public function keywordList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->keywords))));
    }
}