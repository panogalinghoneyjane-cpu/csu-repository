<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
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
        'approved' => 'Approved – Awaiting Upload',
        'rejected' => 'Rejected',
        'needs_revision' => 'Returned for Revision',
        'fulfilled' => 'Added to Repository',
    ];

    public const STATUS_COLORS = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-blue-100 text-blue-800',
        'rejected' => 'bg-red-100 text-red-800',
        'needs_revision' => 'bg-orange-100 text-orange-800',
        'fulfilled' => 'bg-green-100 text-green-800',
    ];

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
