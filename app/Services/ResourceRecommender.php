<?php

namespace App\Services;

use App\Models\AccessLog;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Metadata-based recommender (no AI, as stated in the Scope and Limitations).
 *
 * A candidate resource earns points when it:
 *   +3  is in the same SUBJECT as a matched resource
 *   +2  shares a KEYWORD with the search words or the matched resources
 *   +1  is in the same PROGRAM as a matched resource
 *   +1  was also opened by users who opened the matched resources ("others also read")
 * Ties are broken by popularity (number of views).
 */
class ResourceRecommender
{
    /**
     * Related resources for a search.
     *
     * @param  Collection<int, Resource>  $results  resources that matched the search
     */
    public function forSearch(string $term, Collection $results, User $user, int $limit = 6): Collection
    {
        $words = collect(preg_split('/[\s,]+/', mb_strtolower($term)))->filter(fn ($w) => mb_strlen($w) > 2);

        if ($results->isEmpty()) {
            // Nothing matched: suggest popular resources from the user's college, then overall.
            return $this->popular($user, $limit, $words);
        }

        return $this->score($results, $words, $limit);
    }

    /** Related resources shown beside the reader. */
    public function forResource(Resource $resource, int $limit = 5): Collection
    {
        return $this->score($resource->newCollection([$resource]), collect(), $limit);
    }

    private function score(Collection $seeds, Collection $words, int $limit): Collection
    {
        $seeds->loadMissing('subject');
        $seedIds = $seeds->pluck('id');
        $subjectIds = $seeds->pluck('subject_id')->unique();
        $programIds = $seeds->pluck('subject.program_id')->unique();

        $keywords = $seeds->flatMap(fn (Resource $r) => $r->keywordList())
            ->map(fn ($k) => mb_strtolower($k))
            ->merge($words)
            ->unique();

        // Users who opened the seed resources, and what else they opened.
        $coReaders = AccessLog::whereIn('resource_id', $seedIds)->distinct()->pluck('user_id');
        $alsoRead = AccessLog::whereIn('user_id', $coReaders)
            ->whereNotIn('resource_id', $seedIds)
            ->pluck('resource_id')
            ->countBy();

        $candidates = Resource::active()
            ->with('subject.program')
            ->withCount('accessLogs')
            ->whereNotIn('id', $seedIds)
            ->where(function ($q) use ($programIds, $keywords, $alsoRead) {
                $q->whereHas('subject', fn ($s) => $s->whereIn('program_id', $programIds))
                    ->orWhereIn('id', $alsoRead->keys());
                foreach ($keywords->take(10) as $keyword) {
                    $q->orWhere('keywords', 'like', '%'.$keyword.'%');
                }
            })
            ->limit(200)
            ->get();

        return $candidates
            ->map(function (Resource $r) use ($subjectIds, $programIds, $keywords, $alsoRead) {
                $score = 0;
                $reasons = [];

                if ($subjectIds->contains($r->subject_id)) {
                    $score += 3;
                    $reasons[] = 'Same subject';
                }

                $shared = collect($r->keywordList())->map(fn ($k) => mb_strtolower($k))->intersect($keywords);
                if ($shared->isNotEmpty()) {
                    $score += 2 * min($shared->count(), 3);
                    $reasons[] = 'Shared topic: '.$shared->take(2)->implode(', ');
                }

                if ($programIds->contains($r->subject?->program_id)) {
                    $score += 1;
                    if (empty($reasons)) {
                        $reasons[] = 'Same program';
                    }
                }

                if ($alsoRead->has($r->id)) {
                    $score += 1;
                    $reasons[] = 'Others also read this';
                }

                $r->setAttribute('recommend_score', $score);
                $r->setAttribute('recommend_reason', implode(' · ', $reasons));

                return $r;
            })
            ->filter(fn ($r) => $r->recommend_score > 0)
            ->sortByDesc(fn ($r) => $r->recommend_score * 100000 + $r->access_logs_count)
            ->take($limit)
            ->values();
    }

    private function popular(User $user, int $limit, Collection $words): Collection
    {
        $query = Resource::active()->with('subject.program')->withCount('accessLogs');

        if ($user->college_id) {
            $query->whereHas('subject.program', fn ($p) => $p->where('college_id', $user->college_id));
        }

        $items = $query->orderByDesc('access_logs_count')->limit($limit)->get();

        if ($items->count() < $limit) {
            $more = Resource::active()->with('subject.program')->withCount('accessLogs')
                ->whereNotIn('id', $items->pluck('id'))
                ->orderByDesc('access_logs_count')->limit($limit - $items->count())->get();
            $items = $items->merge($more);
        }

        return $items->each(fn ($r) => $r->setAttribute('recommend_reason', 'Popular in the library'));
    }
}
