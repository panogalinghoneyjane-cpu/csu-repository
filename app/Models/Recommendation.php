<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
        /** Faculty's comment when resubmitting a revision (saved in the history, not in this table). */
    public ?string $revisionNote = null;

    protected $fillable = [
        'faculty_id', 'subject_id', 'title', 'authors', 'publisher', 'edition', 'publication_year',
        'resource_type', 'source_link', 'reason', 'status', 'librarian_remarks', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

     public const STATUS_LABELS = [
        'pending' => 'Pending Review',
                'approved' => 'Pending - For Purchase',      // approved by the librarian, waiting to be bought
        'needs_revision' => 'Returned for Revision',
        'rejected' => 'Rejected',
        'fulfilled' => 'Added to the Repository',    // last step: bought and uploaded
    ];

    public const STATUS_COLORS = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-blue-100 text-blue-800',
        'rejected' => 'bg-red-100 text-red-800',
        'needs_revision' => 'bg-orange-100 text-orange-800',
        'fulfilled' => 'bg-green-100 text-green-800',
    ];
    
        /** Save a history step every time the recommendation is submitted or its status changes. */
    protected static function booted(): void
    {
        static::created(fn (Recommendation $r) => $r->logHistory('submitted'));

        static::updated(function (Recommendation $r) {
            if (! $r->wasChanged('status') && ! $r->wasChanged('reviewed_at')) {
                return;
            }
            $isDecision = in_array($r->status, ['approved', 'rejected', 'needs_revision']);
            $r->logHistory(
                $r->status === 'pending' ? 'resubmitted' : $r->status,
                $isDecision ? $r->librarian_remarks : ($r->status === 'pending' ? $r->revisionNote : null)
            );
        });
    }

        public function logHistory(string $action, ?string $remarks = null): void
    {
        // When the faculty submits or resubmits, keep a copy of what was sent
        $details = in_array($action, ['submitted', 'resubmitted'])
            ? $this->only(['title', 'authors', 'publisher', 'edition', 'publication_year', 'resource_type', 'source_link', 'reason'])
            : null;

        $this->histories()->create([
            'action' => $action,
            'remarks' => $remarks,
            'details' => $details,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
        ]);
    }

    public function histories()
    {
        return $this->hasMany(RecommendationHistory::class);
    }
    
    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function resource()
    {
        return $this->hasOne(Resource::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}
