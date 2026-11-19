<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A faculty member's class for one subject (shown in "My Classes"). */
class CourseClass extends Model
{
    public const SEMESTERS = ['1st Semester', '2nd Semester', 'Summer'];

    protected $fillable = ['faculty_id', 'subject_id', 'section', 'school_year', 'semester', 'description', 'code', 'is_open'];

    protected function casts(): array
    {
        return ['is_open' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Every class gets a unique share code, e.g. "K7Q2MX"
        static::creating(function (CourseClass $class) {
            $class->code ??= self::newCode();
        });
    }

    public static function newCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // no 0/O or 1/I, so codes are easy to type
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function resources()
    {
        return $this->belongsToMany(Resource::class)->withPivot('note')->withTimestamps();
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'course_class_student')->withTimestamps();
    }

    /** "IT 101 – PRS1" */
    public function getTitleAttribute(): string
    {
        return $this->subject->code.' – '.$this->section;
    }

    public function shareUrl(): string
    {
        return route('classes.join', $this->code);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user && $user->id === $this->faculty_id;
    }

    public function hasStudent(?User $user): bool
    {
        return $user && $this->students()->whereKey($user->id)->exists();
    }

    /** Who may open the class page: its teacher, students who saved it, and librarians. */
    public function canBeViewedBy(?User $user): bool
    {
        return $user && ($this->isOwnedBy($user) || $user->isLibrarian() || $this->hasStudent($user));
    }

    public static function normalizeCode(string $code): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $code));
    }
}
