<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\College;
use App\Models\Resource;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * "Recommended for You" on the dashboard (metadata-based, no AI).
 *
 * A resource earns points when it:
 *   +3  belongs to the user's PROGRAM
 *   +2  belongs to the user's COLLEGE (other programs)
 *   +1  is a General Education (GE) resource
 *   +2  matches a word the user SEARCHED recently (per word, max 3 words)
 *   +2  is in the same SUBJECT as something the user READ recently
 * Ties are broken by popularity (number of views).
 * Resources the user already opened are skipped (they are in "Continue Reading").
 */
class PersonalRecommender
{
    public function forUser(User $user, int $limit = 6): Collection
    {
        $user->loadMissing('program');
        $programId = $user->program_id;
        $collegeId = $user->college_id;
        $geId = College::where('code', 'GE')->value('id');

        // Words from the user's recent searches
        $words = SearchLog::where('user_id', $user->id)->latest('searched_at')->take(15)->pluck('keyword')
            ->flatMap(fn ($k) => preg_split('/[\s,]+/', mb_strtolower($k)))
            ->filter(fn ($w) => mb_strlen($w) > 2 && ! in_array($w, ['the', 'and', 'for', 'with', 'from', 'into'], true))
            ->unique()->take(8)->values();

        // What the user has already opened
        $readIds = AccessLog::where('user_id', $user->id)->latest('accessed_at')->take(50)->pluck('resource_id')->unique();
        $readSubjectIds = Resource::whereIn('id', $readIds)->pluck('subject_id')->unique();

        $collegeIds = array_values(array_filter([$collegeId, $geId]));

        $candidates = Resource::active()
            ->with('subject.program')
            ->withCount('accessLogs')
            ->whereNotIn('id', $readIds)
            // Only the user's college + General Education (when the user has a college)
            ->when($collegeIds && $collegeId, fn ($q) => $q->whereHas('subject.program', fn ($p) => $p->whereIn('college_id', $collegeIds)))
            ->limit(300)
            ->get();

        $items = $candidates
            ->map(function (Resource $r) use ($programId, $collegeId, $geId, $words, $readSubjectIds) {
                $score = 0;
                $reasons = [];
                $resourceCollege = $r->subject?->program?->college_id;

                if ($programId && $r->subject?->program_id == $programId) {
                    $score += 3;
                    $reasons[] = 'For your program';
                } elseif ($collegeId && $resourceCollege == $collegeId) {
                    $score += 2;
                    $reasons[] = 'From your college';
                } elseif ($geId && $resourceCollege == $geId) {
                    $score += 1;
                    $reasons[] = 'General Education';
                }

                $text = mb_strtolower($r->title.' '.$r->keywords.' '.$r->authors.' '.$r->subject?->name);
                $matched = $words->filter(fn ($w) => str_contains($text, $w));
                if ($matched->isNotEmpty()) {
                    $score += 2 * min($matched->count(), 3);
                    $reasons[] = 'You searched “'.$matched->take(2)->implode(', ').'”';
                }

                if ($readSubjectIds->contains($r->subject_id)) {
                    $score += 2;
                    $reasons[] = 'Similar to what you read';
                }

                $r->setAttribute('recommend_score', $score);
                $r->setAttribute('recommend_reason', implode(' · ', $reasons));

                return $r;
            })
            ->filter(fn ($r) => $r->recommend_score > 0)
            ->sortByDesc(fn ($r) => $r->recommend_score * 100000 + $r->access_logs_count)
            ->take($limit)
            ->values();

        // Not enough yet (new user): fill with the most viewed in the library
        if ($items->count() < $limit) {
            $more = Resource::active()->with('subject.program')->withCount('accessLogs')
                ->whereNotIn('id', $items->pluck('id')->merge($readIds))
                ->when($collegeIds && $collegeId, fn ($q) => $q->whereHas('subject.program', fn ($p) => $p->whereIn('college_id', $collegeIds)))
                ->orderByDesc('access_logs_count')
                ->limit($limit - $items->count())->get()
                ->each(fn ($r) => $r->setAttribute('recommend_reason', 'Popular in the library'));
            $items = $items->merge($more);
        }

        return $items;
    }
}
