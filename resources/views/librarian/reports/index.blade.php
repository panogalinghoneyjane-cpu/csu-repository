@extends('layouts.app')
@section('title', 'Reports')

@section('content')
@php
    $statusColors = ['Sufficient' => 'bg-green-100 text-green-800', 'Needs Update' => 'bg-orange-100 text-orange-800', 'Lacking Resources' => 'bg-red-100 text-red-800'];
@endphp

<h1 class="text-2xl font-bold mb-4">Reports</h1>

{{-- ============================================================
     SUBSECTION 1: GENERATE REPORTS (the report generator)
     ============================================================ --}}
<section>
    <h2 class="text-lg font-semibold mb-1">Generate Reports</h2>
    <p class="text-sm text-gray-500 mb-4">Choose the criteria, click Generate, then print or export the report.</p>

    {{-- Report criteria --}}
    <form class="no-print bg-white rounded-lg border p-4 mb-6 space-y-3 text-sm">
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
                <thead class="text-left text-gray-500"><tr><th class="py-2">College</th><th>Program</th><th>Courses</th><th>Resources</th><th>Outdated</th><th>Courses Lacking Resources</th></tr></thead>
                <tbody>
                @forelse ($programs as $p)
                    <tr class="border-t">
                        <td class="py-2">{{ $p['college'] }}</td><td>{{ $p['program'] }}</td><td>{{ $p['subjects'] }}</td>
                        <td>{{ $p['resources'] }}</td><td>{{ $p['outdated'] }}</td>
                        {{-- How many courses in this program have fewer than 5 resources (the minimum is per course, not per program) --}}
                        <td class="{{ $p['insufficient'] ? 'text-red-600 font-semibold' : 'text-green-700' }}">{{ $p['insufficient'] ?: 'None' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-3 text-gray-500">No data for the selected criteria.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Report 2: by course --}}
    <div class="bg-white rounded-lg border p-5 mb-6">
        <h3 class="font-semibold mb-1">Resource Adequacy by Course</h3>
        <p class="text-xs text-gray-500 mb-3">
            Every course needs at least <strong>{{ \App\Models\Subject::MIN_RESOURCES }}</strong> resources
        </p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-gray-500"><tr><th class="py-2">College</th><th>Program</th><th>Course</th><th>Resources</th><th>Up-to-date</th><th>Outdated</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($subjects as $s)
                    <tr class="border-t">
                        <td class="py-2">{{ $s['college'] }}</td><td>{{ $s['program'] }}</td>
                        <td><a href="{{ route('browse.subject', $s['id']) }}" class="hover:underline">{{ $s['subject'] }}</a></td>
                        <td>{{ $s['resources'] }}</td><td>{{ $s['current'] }}</td><td>{{ $s['outdated'] }}</td>
                        <td><span class="text-xs px-2 py-0.5 rounded whitespace-nowrap {{ $statusColors[$s['status']] }}">{{ $s['status'] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-3 text-gray-500">No data for the selected criteria.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-xs text-gray-400">Generated {{ now()->format('F d, Y h:i A') }} by {{ auth()->user()->name }} · CSU Digital Library Repository</p>
</section>

@endsection