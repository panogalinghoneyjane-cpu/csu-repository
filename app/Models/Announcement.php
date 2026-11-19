<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    public const AUDIENCES = ['all' => 'Faculty and Students', 'faculty' => 'Faculty only', 'student' => 'Students only'];

    protected $fillable = ['user_id', 'title', 'body', 'image_path', 'audience', 'event_date', 'show_until', 'is_pinned'];

    protected $casts = [
        'event_date' => 'date',
        'show_until' => 'date',
        'is_pinned' => 'boolean',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Still showing (not past its "show until" date). */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('show_until')->orWhereDate('show_until', '>=', today()));
    }

    /** Announcements a user should see on their dashboard. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->current()
            ->whereIn('audience', ['all', $user->role])
            ->orderByDesc('is_pinned')
            ->latest();
    }

    /** Web address of the poster, or null when there is none. */
    public function imageUrl(): ?string
    {
        return $this->image_path && file_exists(public_path($this->image_path)) ? asset($this->image_path) : null;
    }

    /** Remove the poster file from the folder. */
    public function deleteImage(): void
    {
        if ($this->image_path && file_exists(public_path($this->image_path))) {
            @unlink(public_path($this->image_path));
        }
    }

    public function isExpired(): bool
    {
        return $this->show_until && $this->show_until->lt(today());
    }
}
