<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One step in the history of a faculty recommendation. */
class RecommendationHistory extends Model
{
    protected $fillable = ['recommendation_id', 'action', 'remarks', 'details', 'user_id'];

    protected function casts(): array
    {
        return ['details' => 'array'];   // copy of what was submitted at that time
    }

    public const ACTION_LABELS = [
        'submitted' => 'Submitted',
        'resubmitted' => 'Revised and resubmitted',
        'approved' => 'Approved for purchase',
        'rejected' => 'Rejected',
        'needs_revision' => 'Returned for revision',
        'fulfilled' => 'Added to the repository',
    ];

    public const ACTION_COLORS = [
        'submitted' => 'bg-gray-400',
        'resubmitted' => 'bg-yellow-500',
        'approved' => 'bg-blue-600',
        'rejected' => 'bg-red-600',
        'needs_revision' => 'bg-orange-500',
        'fulfilled' => 'bg-green-600',
    ];

    /** Field names shown when viewing a submitted version. */
    public const DETAIL_LABELS = [
        'title' => 'Title',
        'authors' => 'Author(s)',
        'publisher' => 'Publisher',
        'edition' => 'Edition',
        'publication_year' => 'Publication Year',
        'resource_type' => 'Resource Type',
        'source_link' => 'Link / Source',
        'reason' => 'Reason',
    ];

    public function recommendation()
    {
        return $this->belongsTo(Recommendation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return self::ACTION_LABELS[$this->action] ?? $this->action;
    }

    public function color(): string
    {
        return self::ACTION_COLORS[$this->action] ?? 'bg-gray-400';
    }
}
