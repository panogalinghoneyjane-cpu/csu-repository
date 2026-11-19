<?php

namespace App\Http\Controllers\Librarian;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\College;
use App\Models\Program;
use App\Models\Recommendation;
use App\Models\Resource;
use App\Models\SearchLog;
use App\Models\Subject;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Monitoring and Reporting Module.
 * Filters: college, program, resource type, academic year, date range (for usage).
 * Shows: availability, adequacy, relevance (outdated), usage, unmet demand, recommendation status.
 */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        $f = $this->filters($request);
        $subjects = $this->subjectRows($f);

        $resourceBase = $this->resourceQuery($f);
        $usageBase = AccessLog::whereBetween('accessed_at', [$f['from'], $f['to']])
            ->whereIn('resource_id', (clone $resourceBase)->select('id'));

        $summary = [
            'resources' => (clone $resourceBase)->count(),
            'outdated' => (clone $resourceBase)->outdated()->count(),
            'subjects' => $subjects->count(),
            'insufficient' => $subjects->where('status', 'Lacking Resources')->count(),
            'needs_update' => $subjects->where('status', 'Needs Update')->count(),
            'views' => (clone $usageBase)->count(),
            'readers' => (clone $usageBase)->distinct('user_id')->count('user_id'),
            'searches' => SearchLog::whereBetween('searched_at', [$f['from'], $f['to']])->count(),
        ];

        return view('librarian.reports.index', [
            'f' => $f,
            'summary' => $summary,
            'subjects' => $subjects,
            'programs' => $subjects->groupBy('program')->map(fn ($rows, $program) => [
                'college' => $rows->first()['college'],
                'program' => $program,
                'subjects' => $rows->count(),
                'resources' => $rows->sum('resources'),
                'outdated' => $rows->sum('outdated'),
                'views' => $rows->sum('views'),
                'insufficient' => $rows->where('status', 'Lacking Resources')->count(),            ])->values(),
            'topResources' => (clone $resourceBase)->with('subject')
                ->withCount(['accessLogs' => fn ($q) => $q->whereBetween('accessed_at', [$f['from'], $f['to']])])
                ->orderByDesc('access_logs_count')->take(10)->get(),
            'byRole' => (clone $usageBase)->join('users', 'users.id', '=', 'access_logs.user_id')
                ->selectRaw('users.role, count(*) as total')->groupBy('users.role')->pluck('total', 'role'),
            'monthly' => $this->monthlyViews($f),
            'recommendations' => Recommendation::whereBetween('created_at', [$f['from'], $f['to']])
                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'colleges' => College::orderBy('code')->get(),
            'programList' => Program::when($f['college'], fn ($q, $c) => $q->where('college_id', $c))->orderBy('code')->get(),
            'types' => Resource::TYPES,
            'academicYears' => Resource::whereNotNull('academic_year')->distinct()->orderByDesc('academic_year')->pluck('academic_year'),
        ]);
    }

        /** Monitoring numbers shown on the librarian Dashboard. */
    public function monitoringData(Request $request): array
    {
        $f = $this->filters($request);
        $subjects = $this->subjectRows($f);
        $resourceBase = $this->resourceQuery($f);
        $usageBase = AccessLog::whereBetween('accessed_at', [$f['from'], $f['to']])
            ->whereIn('resource_id', (clone $resourceBase)->select('id'));

        return [
            'f' => $f,
            'subjects' => $subjects,
            'summary' => [
                'resources' => (clone $resourceBase)->count(),
                'outdated' => (clone $resourceBase)->outdated()->count(),
                'subjects' => $subjects->count(),
                'insufficient' => $subjects->where('status', 'Lacking Resources')->count(),
            ],
            'monthly' => $this->monthlyViews($f),
            'byRole' => (clone $usageBase)->join('users', 'users.id', '=', 'access_logs.user_id')
                ->selectRaw('users.role, count(*) as total')->groupBy('users.role')->pluck('total', 'role'),
            'recommendations' => Recommendation::whereBetween('created_at', [$f['from'], $f['to']])
                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'topResources' => (clone $resourceBase)->with('subject')
                ->withCount(['accessLogs' => fn ($q) => $q->whereBetween('accessed_at', [$f['from'], $f['to']])])
                ->orderByDesc('access_logs_count')->take(10)->get(),
        ];
    }
    
    /** Download the subject adequacy report as a CSV file (opens in Excel). */
    public function export(Request $request): StreamedResponse
    {
        $f = $this->filters($request);
        $rows = $this->subjectRows($f);
        $filename = 'repository-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows, $f) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['CSU Digital Library Repository - Resource Adequacy Report']);
            fputcsv($out, ['Usage period', $f['from']->toDateString().' to '.$f['to']->toDateString()]);
            fputcsv($out, []);
            fputcsv($out, ['College', 'Program', 'Course', 'Target', 'Resources', 'Up-to-date', 'Outdated', 'Views', 'Status']);
            foreach ($rows as $r) {
                fputcsv($out, [$r['college'], $r['program'], $r['subject'], $r['target'], $r['resources'],
                    $r['current'], $r['outdated'], $r['views'], $r['status']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
    /** Access Logs: who opened which resource, and when (librarian only). */
    public function accessLogs(Request $request)
    {
        $f = $this->filters($request);
        $role = in_array($request->query('role'), ['student', 'faculty', 'librarian'], true) ? $request->query('role') : null;
        $search = trim((string) $request->query('q', ''));

        $logs = AccessLog::with(['user', 'resource.subject'])
            ->whereBetween('accessed_at', [$f['from'], $f['to']])
            ->whereIn('resource_id', $this->resourceQuery($f)->select('id'))
            ->when($role, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('role', $role)))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('user', fn ($u) => $u->where(fn ($x) => $x->where('name', 'like', "%{$search}%")->orWhere('id_number', 'like', "%{$search}%")))
                ->orWhereHas('resource', fn ($r) => $r->where('title', 'like', "%{$search}%"))))
            ->latest('accessed_at')
            ->paginate(25)
            ->withQueryString();

        return view('librarian.reports.access-logs', compact('logs', 'f', 'role', 'search'));
    }
    private function filters(Request $request): array
    {
        return [
            'college' => $request->query('college'),
            'program' => $request->query('program'),
            'type' => $request->query('type'),
            'academic_year' => $request->query('academic_year'),
            'from' => $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : now()->subMonths(6)->startOfDay(),
            'to' => $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : now()->endOfDay(),
        ];
    }

    private function resourceQuery(array $f)
    {
        return Resource::active()
            ->when($f['type'], fn ($q, $t) => $q->where('resource_type', $t))
            ->when($f['academic_year'], fn ($q, $y) => $q->where('academic_year', $y))
            ->when($f['program'], fn ($q, $p) => $q->whereHas('subject', fn ($s) => $s->where('program_id', $p)))
            ->when($f['college'], fn ($q, $c) => $q->whereHas('subject.program', fn ($p) => $p->where('college_id', $c)));
    }

    /** One row per subject with its adequacy status. */
    private function subjectRows(array $f)
    {
        $cutoff = now()->year - Resource::OUTDATED_AFTER_YEARS;
        $scope = function ($q) use ($f) {
            $q->where('resources.status', 'active')
                ->when($f['type'], fn ($q, $t) => $q->where('resources.resource_type', $t))
                ->when($f['academic_year'], fn ($q, $y) => $q->where('resources.academic_year', $y));
        };

        return Subject::with('program.college')
            ->when($f['program'], fn ($q, $p) => $q->where('program_id', $p))
            ->when($f['college'], fn ($q, $c) => $q->whereHas('program', fn ($p) => $p->where('college_id', $c)))
            ->withCount([
                'resources as total_count' => $scope,
                'resources as outdated_count' => fn ($q) => $scope($q->where('resources.publication_year', '<', $cutoff)),
                'accessLogs as views_count' => fn ($q) => $scope($q->whereBetween('access_logs.accessed_at', [$f['from'], $f['to']])),
            ])
            ->get()
            ->map(function (Subject $s) {
                $current = $s->total_count - $s->outdated_count;
                $min = Subject::MIN_RESOURCES;   // the same minimum (5) for every course
                $status = match (true) {
                    $current < $min && $s->total_count < $min => 'Lacking Resources',
                    $current < $min => 'Needs Update',
                    default => 'Sufficient',
                };

                return [
                    'id' => $s->id,
                    'college' => $s->program->college->code,
                    'program' => $s->program->code,
                    'subject' => $s->label,
                    'target' => $min,
                    'resources' => $s->total_count,
                    'current' => $current,
                    'outdated' => $s->outdated_count,
                    'views' => $s->views_count,
                    'status' => $status,
                ];
            })
            ->sortBy([['college', 'asc'], ['program', 'asc'], ['subject', 'asc']])
            ->values();
    }

    private function monthlyViews(array $f)
    {
        $months = collect();
        $cursor = $f['from']->copy()->startOfMonth();
        while ($cursor <= $f['to'] && $months->count() < 12) {
            $months->push([
                'label' => $cursor->format('M Y'),
                'total' => AccessLog::whereBetween('accessed_at', [$cursor->copy()->startOfMonth(), $cursor->copy()->endOfMonth()])->count(),
            ]);
            $cursor->addMonth();
        }

        return $months;
    }
}
