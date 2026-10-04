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
        'resource_type', 'academic_year', 'description', 'keywords', 'file_path', 'page_count', 'status',
    ];

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

    /** Simple keyword search across title, authors, keywords, description and subject. */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $stop = ['the', 'and', 'for', 'with', 'from', 'into', 'of', 'in', 'to', 'a', 'an', 'on', 'by', '&', '-', ':'];
        $words = array_filter(preg_split('/\s+/', trim($term)),
            fn ($w) => mb_strlen($w) >= 3 && ! in_array(mb_strtolower(trim($w, ':,.;()')), $stop, true));
        $words = array_map(fn ($w) => trim($w, ':,.;()'), $words);
        if (empty($words)) {
            $words = [trim($term)];
        }

        return $query->where(function (Builder $q) use ($words) {
            foreach ($words as $word) {
                $like = '%'.$word.'%';
                $q->orWhere('title', 'like', $like)
                    ->orWhere('authors', 'like', $like)
                    ->orWhere('keywords', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('subject', fn ($s) => $s->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('code', 'like', $like)));
            }
        });
    }

    public function isOutdated(): bool
    {
        return $this->publication_year < now()->year - self::OUTDATED_AFTER_YEARS;
    }

    public function keywordList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->keywords))));
    }
}
