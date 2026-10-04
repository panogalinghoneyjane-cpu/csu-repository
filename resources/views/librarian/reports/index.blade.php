@extends('layouts.app')
@section('title', 'Reports & Monitoring')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-2xl font-bold">Reports & Monitoring</h1>
        <p class="text-sm text-gray-500">Usage period: {{ $f['from']->format('M d, Y') }} – {{ $f['to']->format('M d, Y') }}</p>
    </div>
    <div class="no-print flex gap-2">
        <a href="{{ route('librarian.reports.export', request()->query()) }}" class="border bg-white px-4 py-2 rounded hover:bg-gray-50 text-sm">⬇ Export CSV (Excel)</a>
        <button onclick="window.print()" class="bg-csu-700 text-white px-4 py-2 rounded hover:bg-csu-800 text-sm">🖨 Print Report</button>
    </div>
</div>

{{-- Filters --}}
<form class="no-print bg-white rounded-lg border p-4 mb-6 grid sm:grid-cols-3 lg:grid-cols-7 gap-3 text-sm">
    <select name="college" class="border rounded px-3 py-2" onchange="this.form.program.value=''; this.form.submit()">
        <option value="">All colleges</option>
        @foreach ($colleges as $c)<option value="{{ $c->id }}" @selected($f['college'] == $c->id)>{{ $c->code }}</option>@endforeach
    </select>
    <select name="program" class="border rounded px-3 py-2">
        <option value="">All programs</option>
        @foreach ($programList as $p)<option value="{{ $p->id }}" @selected($f['program'] == $p->id)>{{ $p->code }}</option>@endforeach
    </select>
    <select name="type" class="border rounded px-3 py-2">
        <option value="">All types</option>
        @foreach ($types as $t)<option @selected($f['type'] === $t)>{{ $t }}</option>@endforeach
    </select>
    <select name="academic_year" class="border rounded px-3 py-2">
        <option value="">All academic years</option>
        @foreach ($academicYears as $y)<option @selected($f['academic_year'] === $y)>{{ $y }}</option>@endforeach
    </select>
    <input type="date" name="from" value="{{ $f['from']->toDateString() }}" class="border rounded px-3 py-2" title="Usage from">
    <input type="date" name="to" value="{{ $f['to']->toDateString() }}" class="border rounded px-3 py-2" title="Usage to">
    <button class="bg-gray-800 text-white px-3 py-2 rounded">Generate</button>
</form>

{{-- Summary --}}
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3 mb-6">
    @foreach ([
        ['Resources', $summary['resources']], ['Outdated', $summary['outdated']], ['Subjects', $summary['subjects']],
        ['Insufficient', $summary['insufficient']], ['Needs Update', $summary['needs_update']],
        ['Views', $summary['views']], ['Unique Readers', $summary['readers']], ['Searches', $summary['searches']],
    ] as [$label, $value])
        <div class="bg-white rounded-lg border p-3">
            <p class="text-xs text-gray-500">{{ $label }}</p>
            <p class="text-2xl font-bold">{{ number_format($value) }}</p>
        </div>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    {{-- Monthly views bar chart (pure CSS) --}}
    <section class="bg-white rounded-lg border p-5 lg:col-span-2">
        <h2 class="font-semibold mb-4">Repository Usage per Month</h2>
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
    </section>

    <section class="bg-white rounded-lg border p-5">
        <h2 class="font-semibold mb-3">Usage by Role</h2>
        @foreach (['student' => 'Students', 'faculty' => 'Faculty', 'librarian' => 'Librarians'] as $role => $label)
            <div class="flex justify-between border-t py-2 text-sm"><span>{{ $label }}</span><span class="font-semibold">{{ $byRole[$role] ?? 0 }}</span></div>
        @endforeach
        <h2 class="font-semibold mt-5 mb-3">Faculty Recommendations</h2>
        @foreach (\App\Models\Recommendation::STATUS_LABELS as $status => $label)
            <div class="flex justify-between border-t py-2 text-sm"><span>{{ $label }}</span><span class="font-semibold">{{ $recommendations[$status] ?? 0 }}</span></div>
        @endforeach
    </section>
</div>

{{-- Adequacy by program --}}
<section class="bg-white rounded-lg border p-5 mb-6">
    <h2 class="font-semibold mb-3">Collection Summary by Program</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-gray-500"><tr><th class="py-2">College</th><th>Program</th><th>Subjects</th><th>Resources</th><th>Outdated</th><th>Views</th><th>Subjects Lacking Resources</th></tr></thead>
            <tbody>
            @foreach ($programs as $p)
                <tr class="border-t">
                    <td class="py-2">{{ $p['college'] }}</td><td>{{ $p['program'] }}</td><td>{{ $p['subjects'] }}</td>
                    <td>{{ $p['resources'] }}</td><td>{{ $p['outdated'] }}</td><td>{{ $p['views'] }}</td>
                    <td class="{{ $p['insufficient'] ? 'text-red-600 font-semibold' : 'text-green-700' }}">{{ $p['insufficient'] ?: 'None' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>

{{-- Adequacy by subject --}}
<section class="bg-white rounded-lg border p-5 mb-6">
    <h2 class="font-semibold mb-1">Resource Adequacy by Subject</h2>
    <p class="text-xs text-gray-500 mb-3">
        <strong>Sufficient</strong>: meets the target with up-to-date resources ·
        <strong>Needs Update</strong>: has enough resources but too many are older than {{ \App\Models\Resource::OUTDATED_AFTER_YEARS }} years ·
        <strong>Insufficient</strong>: fewer resources than the target.
    </p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-gray-500"><tr><th class="py-2">College</th><th>Program</th><th>Subject</th><th>Target</th><th>Resources</th><th>Up-to-date</th><th>Outdated</th><th>Views</th><th>Status</th></tr></thead>
            <tbody>
            @foreach ($subjects as $s)
                <tr class="border-t">
                    <td class="py-2">{{ $s['college'] }}</td><td>{{ $s['program'] }}</td>
                    <td><a href="{{ route('browse.subject', $s['id']) }}" class="hover:underline">{{ $s['subject'] }}</a></td>
                    <td>{{ $s['target'] }}</td><td>{{ $s['resources'] }}</td><td>{{ $s['current'] }}</td><td>{{ $s['outdated'] }}</td><td>{{ $s['views'] }}</td>
                    <td>
                        <span class="text-xs px-2 py-0.5 rounded whitespace-nowrap {{ ['Sufficient' => 'bg-green-100 text-green-800', 'Needs Update' => 'bg-orange-100 text-orange-800', 'Insufficient' => 'bg-red-100 text-red-800'][$s['status']] }}">{{ $s['status'] }}</span>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    <section class="bg-white rounded-lg border p-5">
        <h2 class="font-semibold mb-3">Most Viewed Resources</h2>
        <ol class="text-sm space-y-2 list-decimal ml-5">
            @forelse ($topResources as $r)
                <li><span class="font-medium">{{ $r->title }}</span> <span class="text-gray-500">({{ $r->subject->code }}) – {{ $r->access_logs_count }} views</span></li>
            @empty
                <li class="text-gray-500 list-none -ml-5">No usage in this period.</li>
            @endforelse
        </ol>
    </section>
    <section class="bg-white rounded-lg border p-5">
        <h2 class="font-semibold mb-1">Unused Resources</h2>
        <p class="text-xs text-gray-500 mb-3">Not opened even once in this period – consider promoting, replacing, or archiving.</p>
        <ul class="text-sm space-y-2">
            @forelse ($unused as $r)
                <li><span class="font-medium">{{ $r->title }}</span> <span class="text-gray-500">({{ $r->subject->code }}, {{ $r->publication_year }})</span></li>
            @empty
                <li class="text-gray-500">Every resource was used. 🎉</li>
            @endforelse
        </ul>
    </section>
    <section class="bg-white rounded-lg border p-5">
        <h2 class="font-semibold mb-3">Top Search Keywords</h2>
        <ul class="text-sm space-y-1">
            @forelse ($topKeywords as $k)
                <li class="flex justify-between border-t py-1"><span>{{ $k->keyword }}</span><span class="font-semibold">{{ $k->total }}</span></li>
            @empty
                <li class="text-gray-500">No searches in this period.</li>
            @endforelse
        </ul>
    </section>
    <section class="bg-white rounded-lg border p-5">
        <h2 class="font-semibold mb-1">Unmet Demand (Searches With No Results)</h2>
        <p class="text-xs text-gray-500 mb-3">Topics users looked for that the repository does not have – candidates for acquisition.</p>
        <ul class="text-sm space-y-1">
            @forelse ($zeroResults as $k)
                <li class="flex justify-between border-t py-1"><span class="text-red-700">{{ $k->keyword }}</span><span class="font-semibold">{{ $k->total }}×</span></li>
            @empty
                <li class="text-gray-500">None – every search found something.</li>
            @endforelse
        </ul>
    </section>
</div>

<p class="text-xs text-gray-400">Generated {{ now()->format('F d, Y h:i A') }} by {{ auth()->user()->name }} · CSU Digital Library Repository</p>
@endsection
