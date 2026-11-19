<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Resource;
use App\Models\SearchLog;
use App\Models\Subject;
use App\Services\ResourceRecommender;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request, ResourceRecommender $recommender)
    {
        $q = trim((string) $request->query('q', ''));
        $filters = $request->only(['college', 'type', 'year']);

        $results = collect();
        $related = collect();
        $courses = collect();
        $seconds = 0;

        if ($q !== '') {
            $query = Resource::active()->with('subject.program.college')->withCount('accessLogs')->search($q, false);   // false = don't match by course name (courses have their own box)

            if (! empty($filters['college'])) {
                $query->whereHas('subject.program', fn ($p) => $p->where('college_id', $filters['college']));
            }
            if (! empty($filters['type'])) {
                $query->where('resource_type', $filters['type']);
            }
            if (! empty($filters['year'])) {
                $query->where('publication_year', '>=', (int) $filters['year']);
            }

            $started = microtime(true);
            // Order: found in the title first, then everything else (description, keywords, inside the PDF) together.
            // Inside each group: newest to oldest, then the most matches.
            $results = $query->orderByDesc('publication_year')->limit(300)->get()
                ->map(fn ($r) => [$r, min($r->matchTier($q), 2), $r->relevance($q)])
                ->sort(fn ($a, $b) => [$a[1], $b[0]->publication_year, $b[2]] <=> [$b[1], $a[0]->publication_year, $a[2]])
                ->map(fn ($row) => $row[0])
                ->take(50)
                ->values();
            // Keep it a database collection (the Recommended panel needs loadMissing)
            $results = new \Illuminate\Database\Eloquent\Collection($results->all());
            $seconds = round(microtime(true) - $started, 2);

            SearchLog::create([
                'user_id' => $request->user()->id,
                'keyword' => mb_substr($q, 0, 255),
                'results_count' => $results->count(),
                'searched_at' => now(),
            ]);

            // Courses whose code or name contain EVERY search word (shown even when they have no resources yet).
            // A search for only a year shows no courses.
            [$words] = Resource::splitSearch($q);
            $courses = ! $words ? collect() : Subject::with('program.college')->withCount('activeResources')
                ->where(function ($w) use ($words) {
                    foreach ($words as $word) {
                        $w->where(fn ($c) => $c->whereLike('name', '%'.$word.'%')->orWhereLike('code', '%'.$word.'%'));
                    }
                })
                ->orderBy('code')->take(6)->get();

            // Recommended / related resources during search (panel comment)
            $related = $recommender->forSearch($q, $results, $request->user());
        }

        return view('search.index', [
            'q' => $q,
            'filters' => $filters,
            'results' => $results,
            'seconds' => $seconds,
            'related' => $related,
            'courses' => $courses,
            'colleges' => College::orderBy('code')->get(),
            'types' => Resource::TYPES,
        ]);
    }
}