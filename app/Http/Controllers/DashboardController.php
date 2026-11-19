<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Librarian\ReportController;
use App\Models\Subject;
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
            'admin' => redirect()->route('admin.users.index'),
            'librarian' => $this->librarian(),
            'faculty' => $this->faculty($user),
            default => $this->student($user),
        };
    }

    private function librarian()
    {
        return view('dashboard.librarian', array_merge(
            app(ReportController::class)->monitoringData(request()),
            [
                'recent' => Resource::with('subject.program')->latest()->take(5)->get(),
                'pendingList' => Recommendation::with(['faculty', 'subject.program.college'])
                    ->where('status', 'pending')->latest()->get(),
                'outdatedList' => Resource::active()->outdated()
                    ->with('subject.program.college')->orderBy('publication_year')->get(),
            ]
        ));
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
                        'myRecommendations' => $user->recommendations()->with('subject.program.college', 'resource')->latest()->get(),
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
            'popular' => app(\App\Services\PersonalRecommender::class)->forUser($user),
            'recentlyViewed' => Resource::whereIn('id',
                AccessLog::where('user_id', $user->id)->latest('accessed_at')->take(20)->pluck('resource_id')
            )->with('subject')->take(5)->get(),
        ]);
    }
}
