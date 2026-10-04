<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Resource;
use App\Models\SearchLog;
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

        if ($q !== '') {
            $query = Resource::active()->with('subject.program.college')->withCount('accessLogs')->search($q);

            if (! empty($filters['college'])) {
                $query->whereHas('subject.program', fn ($p) => $p->where('college_id', $filters['college']));
            }
            if (! empty($filters['type'])) {
                $query->where('resource_type', $filters['type']);
            }
            if (! empty($filters['year'])) {
                $query->where('publication_year', '>=', (int) $filters['year']);
            }

            $results = $query->orderByDesc('publication_year')->limit(50)->get();

            SearchLog::create([
                'user_id' => $request->user()->id,
                'keyword' => mb_substr($q, 0, 255),
                'results_count' => $results->count(),
                'searched_at' => now(),
            ]);

            // Recommended / related resources during search (panel comment)
            $related = $recommender->forSearch($q, $results, $request->user());
        }

        return view('search.index', [
            'q' => $q,
            'filters' => $filters,
            'results' => $results,
            'related' => $related,
            'colleges' => College::orderBy('code')->get(),
            'types' => Resource::TYPES,
        ]);
    }
}
