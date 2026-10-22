@extends('layouts.app')
@section('title', 'Reports & Monitoring')

@section('content')
@php
    // Which tab is open: "generate" (Generate Reports) or "monitor" (Monitoring Resources)
    $tab = request('tab', 'generate') === 'monitor' ? 'monitor' : 'generate';
    $statusColors = ['Sufficient' => 'bg-green-100 text-green-800', 'Needs Update' => 'bg-orange-100 text-orange-800', 'Insufficient' => 'bg-red-100 text-red-800'];
@endphp

<h1 class="text-2xl font-bold mb-4">Reports & Monitoring</h1>

{{-- ============================================================
     TABS
     ============================================================ --}}
<div class="no-print flex gap-1 border-b mb-6">
    <a href="{{ route('librarian.reports.index', array_merge(request()->except('tab'), ['tab' => 'generate'])) }}"
       class="px-4 py-2 -mb-px border-b-2 font-semibold {{ $tab === 'generate' ? 'border-csu-700 text-csu-800' : 'border-transparent text-gray-500 hover:text-csu-700' }}">
        Generate Reports
    </a>
    <a href="{{ route('librarian.reports.index', array_merge(request()->except('tab'), ['tab' => 'monitor'])) }}"
       class="px-4 py-2 -mb-px border-b-2 font-semibold {{ $tab === 'monitor' ? 'border-csu-700 text-csu-800' : 'border-transparent text-gray-500 hover:text-csu-700' }}">
        Monitoring Resources
    </a>
</div>

@if ($tab === 'generate')
{{-- ============================================================
     SUBSECTION 1: GENERATE REPORTS (the report generator)
     ============================================================ --}}
<section>
    <h2 class="text-lg font-semibold mb-1">Generate Reports</h2>
    <p class="text-sm text-gray-500 mb-4">Choose the criteria, click Generate, then print or export the report.</p>

    {{-- Report criteria --}}
    <form class="no-print bg-white rounded-lg border p-4 mb-6 space-y-3 text-sm">
        <input type="hidden" name="tab" value="generate">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <label class="block">
                <span class="block font-medium mb-1">College</span>
                <select name="college" class="w-full border rounded px-3 py-2" onchange="this.form.program.value=''; this.form.submit()">
                    <option value="">All colleges</option>
                    @foreach ($colleges as $c)<option value="{{ $c->id }}" @selected($f['college'] == $c->id)>{{ $c->code }}</option>@endforeach
                </select>
            </label>
            <label class="block">
                <span class="block font-medium mb-1">Program</span>
                <select name="program" class="w-full border rounded px-3 py-2">
                    <option value="">All programs</option>
                    @foreach ($programList as $p)<option value="{{ $p->id }}" @selected($f['program'] == $p->id)>{{ $p->code }}</option>@endforeach
                </select>
            </label>
            <label class="block">
                <span class="block font-medium mb-1">Resource Type</span>
                <select name="type" class="w-full border rounded px-3 py-2">
                    <option value="">All types</option>
                    @foreach ($types as $t)<option @selected($f['type'] === $t)>{{ $t }}</option>@endforeach
                </select>
            </label>
            <label class="block">
                <span class="block font-medium mb-1">Academic Year</span>
                <select name="academic_year" class="w-full border rounded px-3 py-2">
                    <option value="">All academic years</option>
                    @foreach ($academicYears as $y)<option @selected($f['academic_year'] === $y)>{{ $y }}</option>@endforeach
                </select>
            </label>
            <label class="block">
                <span class="block font-medium mb-1">Usage From</span>
                <input type="date" name="from" value="{{ $f['from']->toDateString() }}" class="w-full border rounded px-3 py-2">
            </label>
            <label class="block">
                <span class="block font-medium mb-1">Usage To</span>
                <input type="date" name="to" value="{{ $f['to']->toDateString() }}" class="w-full border rounded px-3 py-2">
            </label>
        </div>
        <div class="flex flex-wrap gap-2 pt-1">
            <button class="bg-csu-700 text-white px-5 py-2 rounded hover:bg-csu-800">Generate Report</button>
            <a href="{{ route('librarian.reports.export', request()->except('tab')) }}" class="border bg-white px-4 py-2 rounded hover:bg-gray-50">⬇ Export CSV (Excel)</a>
            <button type="button" onclick="window.print()" class="border bg-white px-4 py-2 rounded hover:bg-gray-50">🖨 Print Report</button>
        </div>
    </form>

    {{-- Report header (also appears on the printed copy) --}}
    <div class="mb-4">
        <p class="font-semibold">CSU Digital Library Repository – Resource Report</p>
        <p class="text-sm text-gray-600">
            College: {{ $colleges->firstWhere('id', $f['college'])->code ?? 'All' }} ·
            Program: {{ $programList->firstWhere('id', $f['program'])->code ?? 'All' }} ·
            Type: {{ $f['type'] ?: 'All' }} ·
            Academic Year: {{ $f['academic_year'] ?: 'All' }} ·
            Usage period: {{ $f['from']->format('M d, Y') }} – {{ $f['to']->format('M d, Y') }}
        </p>
    </div>

    {{-- Report 1: by program --}}
    <div class="bg-white rounded-lg border p-5 mb-6">
        <h3 class="font-semibold mb-3">Collection Summary by Program</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-gray-500"><tr><th class="py-2">College</th><th>Program</th><th>Subjects</th><th>Resources</th><th>Outdated</th><th>Views</th><th>Subjects Lacking Resources</th></tr></thead>
                <tbody>
                @forelse ($programs as $p)
                    <tr class="border-t">
                        <td class="py-2">{{ $p['college'] }}</td><td>{{ $p['program'] }}</td><td>{{ $p['subjects'] }}</td>
                        <td>{{ $p['resources'] }}</td><td>{{ $p['outdated'] }}</td><td>{{ $p['views'] }}</td>
                        <td class="{{ $p['insufficient'] ? 'text-red-600 font-semibold' : 'text-green-700' }}">{{ $p['insufficient'] ?: 'None' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-3 text-gray-500">No data for the selected criteria.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Report 2: by subject --}}
    <div class="bg-white rounded-lg border p-5 mb-6">
        <h3 class="font-semibold mb-1">Resource Adequacy by Subject</h3>
        <p class="text-xs text-gray-500 mb-3">
            <strong>Sufficient</strong>: meets the target with up-to-date resources ·
            <strong>Needs Update</strong>: has enough resources but too many are older than {{ \App\Models\Resource::OUTDATED_AFTER_YEARS }} years ·
            <strong>Insufficient</strong>: fewer resources than the target.
        </p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-gray-500"><tr><th class="py-2">College</th><th>Program</th><th>Subject</th><th>Target</th><th>Resources</th><th>Up-to-date</th><th>Outdated</th><th>Views</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($subjects as $s)
                    <tr class="border-t">
                        <td class="py-2">{{ $s['college'] }}</td><td>{{ $s['program'] }}</td>
                        <td><a href="{{ route('browse.subject', $s['id']) }}" class="hover:underline">{{ $s['subject'] }}</a></td>
                        <td>{{ $s['target'] }}</td><td>{{ $s['resources'] }}</td><td>{{ $s['current'] }}</td><td>{{ $s['outdated'] }}</td><td>{{ $s['views'] }}</td>
                        <td><span class="text-xs px-2 py-0.5 rounded whitespace-nowrap {{ $statusColors[$s['status']] }}">{{ $s['status'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-3 text-gray-500">No data for the selected criteria.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400">Generated {{ now()->format('F d, Y h:i A') }} by {{ auth()->user()->name }} · CSU Digital Library Repository</p>
</section>

@else
{{-- ============================================================
     SUBSECTION 2: MONITORING RESOURCES (live overview)
     ============================================================ --}}
<section>
    <h2 class="text-lg font-semibold mb-1">Monitoring Resources</h2>
    <p class="text-sm text-gray-500 mb-4">Current status of the repository. Usage figures cover {{ $f['from']->format('M d, Y') }} – {{ $f['to']->format('M d, Y') }}.</p>

    {{-- Summary numbers --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        @foreach ([
            ['Resources', $summary['resources']], ['Outdated', $summary['outdated']], ['Subjects', $summary['subjects']],
            ['Insufficient Subjects', $summary['insufficient']], ['Subjects Needing Update', $summary['needs_update']],
            ['Views', $summary['views']], ['Unique Readers', $summary['readers']], ['Searches', $summary['searches']],
        ] as [$label, $value])
            <div class="bg-white rounded-lg border p-3">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="text-2xl font-bold">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-3 gap-6 mb-6">
        {{-- Monthly views bar chart --}}
        <div class="bg-white rounded-lg border p-5 lg:col-span-2">
            <h3 class="font-semibold mb-4">Repository Usage per Month</h3>
            @php $max = max(1, $monthly->max('total')); @endphp
            <div class="flex items-end gap-2 h-48">
                @foreach ($monthly as $m)
                    <div class="flex-1 flex flex-col items-center justify-end h-full">
                        <span class="text-xs text-gray-600 mb-1">{{ $m['total'] }}</span>
                        <div class="w-full bg-csu-600 rounded-t" style="height: {{ max(2, $m['total'] / $max * 100) }}%"></div>
                        <span class="text-[10px] text-gray-500 mt-1 whitespace-nowrap">{{ $m['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg border p-5">
            <h3 class="font-semibold mb-3">Usage by Role</h3>
            <p class="text-xs text-gray-500 -mt-2 mb-2">Click a role to see who opened which resources.</p>
            @foreach (['student' => 'Students', 'faculty' => 'Faculty', 'librarian' => 'Librarians'] as $role => $label)
                <a href="{{ route('librarian.access-logs.index', array_merge(request()->except(['tab', 'page']), ['role' => $role])) }}"
                   class="flex justify-between border-t py-2 text-sm hover:bg-csu-50 hover:text-csu-800 -mx-2 px-2 rounded">
                    <span>{{ $label }} <span class="text-csu-700">›</span></span><span class="font-semibold">{{ $byRole[$role] ?? 0 }}</span>
                </a>
            @endforeach
            <a href="{{ route('librarian.access-logs.index', request()->except(['tab', 'page'])) }}" class="block text-xs text-csu-700 hover:underline mt-2">View all access logs ›</a>

            <h3 class="font-semibold mt-5 mb-3">Faculty Recommendations</h3>
            @foreach (\App\Models\Recommendation::STATUS_LABELS as $status => $label)
                <a href="{{ route('librarian.reviews.index', ['status' => $status]) }}"
                   class="flex justify-between border-t py-2 text-sm hover:bg-csu-50 hover:text-csu-800 -mx-2 px-2 rounded">
                    <span>{{ $label }} <span class="text-csu-700">›</span></span><span class="font-semibold">{{ $recommendations[$status] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Most viewed --}}
    <div class="bg-white rounded-lg border p-5">
        <h3 class="font-semibold mb-3">Most Viewed Resources</h3>
        <ol class="text-sm space-y-2 list-decimal ml-5">
            @forelse ($topResources as $r)
                <li><span class="font-medium">{{ $r->title }}</span> <span class="text-gray-500">({{ $r->subject->code }}) – {{ $r->access_logs_count }} views</span></li>
            @empty
                <li class="text-gray-500 list-none -ml-5">No usage in this period.</li>
            @endforelse
        </ol>
    </div>
</section>
@endif
@endsection