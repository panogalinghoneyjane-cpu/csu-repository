<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\College;
use App\Models\Recommendation;
use App\Models\Resource;
use App\Models\SearchLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return match ($user->role) {
            'librarian' => $this->librarian(),
            'faculty' => $this->faculty($user),
            default => $this->student($user),
        };
    }

    private function librarian()
    {
        return view('dashboard.librarian', [
            'stats' => [
                'resources' => Resource::active()->count(),
                'pending' => Recommendation::where('status', 'pending')->count(),
                'to_upload' => Recommendation::where('status', 'approved')->count(),
                'outdated' => Resource::active()->outdated()->count(),
                'views_month' => AccessLog::where('accessed_at', '>=', now()->startOfMonth())->count(),
                'no_result_searches' => SearchLog::where('results_count', 0)
                    ->where('searched_at', '>=', now()->subDays(30))->count(),
            ],
            'pending' => Recommendation::with(['faculty', 'subject.program'])
                ->where('status', 'pending')->latest()->take(5)->get(),
            'recent' => Resource::with('subject.program')->latest()->take(5)->get(),
            'activity' => AccessLog::with(['user', 'resource'])->latest('accessed_at')->take(8)->get(),
        ]);
    }

    private function faculty($user)
    {
        return view('dashboard.faculty', [
            'colleges' => College::withCount('programs')->orderBy('code')->get(),
            'counts' => $user->recommendations()->selectRaw('status, count(*) as total')
                ->groupBy('status')->pluck('total', 'status'),
            // "Updates": recent decisions the librarian made on this faculty's recommendations
            'updates' => $user->recommendations()->with('subject')
                ->whereNotNull('reviewed_at')->latest('reviewed_at')->take(5)->get(),
            'recent' => Resource::active()->with('subject.program')->latest()->take(6)->get(),
        ]);
    }

    private function student($user)
    {
        $collegeQuery = Resource::active()->with('subject.program')->withCount('accessLogs');
        if ($user->college_id) {
            $collegeQuery->whereHas('subject.program', fn ($q) => $q->where('college_id', $user->college_id));
        }

        return view('dashboard.student', [
            'colleges' => College::withCount('programs')->orderBy('code')->get(),
            'popular' => $collegeQuery->orderByDesc('access_logs_count')->take(6)->get(),
            'recentlyViewed' => Resource::whereIn('id',
                AccessLog::where('user_id', $user->id)->latest('accessed_at')->take(20)->pluck('resource_id')
            )->with('subject')->take(5)->get(),
        ]);
    }
}
