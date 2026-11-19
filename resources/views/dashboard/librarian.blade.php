@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $bySubjectCount = $subjects->sortByDesc('resources')->values();
    $insufficientRows = $subjects->where('status', 'Lacking Resources')->sortBy('resources')->values();
    $outdatedBySubject = $outdatedList->groupBy('subject_id');
@endphp

<h1 class="text-2xl font-bold">Dashboard</h1>
<p class="text-sm text-gray-500 mb-6">
    Overview and monitoring of the repository as of {{ now()->format('F d, Y') }}.
    Usage figures cover {{ $f['from']->format('M d, Y') }} – {{ $f['to']->format('M d, Y') }}.
</p>

{{-- ===== Summary numbers (click a box to see the details) ===== --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
    @foreach ([
        ['Total Resources', $summary['resources'], 'text-csu-700', 'm-total'],
        ['Pending Recommendations', $pendingList->count(), 'text-amber-600', 'm-pending'],
        ['Outdated Resources', $summary['outdated'], 'text-red-600', 'm-outdated'],
        ['Courses', $summary['subjects'], 'text-gray-800', 'm-subjects'],
        ['Courses Lacking Resources', $summary['insufficient'], 'text-red-600', 'm-insufficient'],
    ] as [$label, $value, $color, $modal])
        <button type="button" onclick="openModal('{{ $modal }}')"
                class="text-left bg-white rounded-lg border p-4 hover:border-csu-600 hover:shadow-sm transition">
            <p class="text-sm text-gray-500">{{ $label }}</p>
            <p class="text-3xl font-bold {{ $color }}">{{ number_format($value) }}</p>
        </button>
    @endforeach
</div>

@if ($summary['outdated'] > 0)
    <div class="mb-6 rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-900">
        ⚠ {{ $summary['outdated'] }} {{ Str::plural('resource', $summary['outdated']) }} are older than 5 years.
        <button type="button" onclick="openModal('m-outdated')" class="underline font-medium">Review</button>
    </div>
@endif

{{-- ===== Courses with the most resources (top 10) ===== --}}
@include('librarian.reports.partials.top-subjects')

{{-- ===== Usage per month ===== --}}
<div class="bg-white rounded-lg border p-5 mb-6">
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
</div>

{{-- ===== Most viewed + recently added ===== --}}
<div class="grid lg:grid-cols-2 gap-6 items-start">
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
        <div class="flex justify-between items-center mb-3">
            <h2 class="font-semibold">Recently Added Resources</h2>
            <a href="{{ route('librarian.resources.create') }}" class="text-sm bg-csu-700 text-white px-3 py-1 rounded hover:bg-csu-800">+ Upload</a>
        </div>
        @forelse ($recent as $r)
            <div class="border-t py-3">
                <p class="font-medium">{{ $r->title }}</p>
                <p class="text-xs text-gray-500">{{ $r->subject->program->code }} · {{ $r->subject->label }} · {{ $r->created_at->diffForHumans() }}</p>
            </div>
        @empty
            <p class="text-sm text-gray-500">No resources yet.</p>
        @endforelse
    </section>
</div>

{{-- =====================================================================
     DETAIL WINDOWS (open when a summary box is clicked)
     ===================================================================== --}}

{{-- 1. Total Resources: resources per course --}}
<x-dashboard-modal id="m-total" title="Total Resources per Course"
    :subtitle="$summary['resources'].' resources in '.$bySubjectCount->where('resources', '>', 0)->count().' courses'"
    :proceed="route('librarian.resources.index')" proceed-label="Proceed to Manage Resources">
    @foreach ($bySubjectCount as $s)
        <a href="{{ route('librarian.resources.index', ['subject' => $s['id']]) }}"
           class="flex items-center justify-between gap-3 border-t py-2.5 px-2 -mx-2 rounded hover:bg-csu-50">
            <span>
                <span class="font-medium">{{ $s['subject'] }}</span>
                <span class="block text-xs text-gray-500">{{ $s['college'] }} › {{ $s['program'] }}</span>
            </span>
            <span class="font-semibold text-csu-700 whitespace-nowrap">{{ $s['resources'] }} {{ Str::plural('resource', $s['resources']) }} ›</span>
        </a>
    @endforeach
</x-dashboard-modal>

{{-- 2. Pending Recommendations: what was recommended, for which course, by which faculty --}}
<x-dashboard-modal id="m-pending" title="Pending Recommendations"
    :subtitle="$pendingList->count().' waiting for your review'"
    :proceed="route('librarian.reviews.index', ['status' => 'pending'])" proceed-label="Proceed to Review Recommendations">
    @forelse ($pendingList as $rec)
        <a href="{{ route('librarian.reviews.show', $rec) }}"
           class="block border-t py-2.5 px-2 -mx-2 rounded hover:bg-csu-50">
            <span class="font-medium">{{ $rec->title }}</span>
            <span class="block text-xs text-gray-500">
                {{ $rec->subject->label }} · {{ $rec->subject->program->college->code }} › {{ $rec->subject->program->code }}
            </span>
            <span class="block text-xs text-gray-600">Recommended by <strong>{{ $rec->faculty->name }}</strong> · {{ $rec->created_at->diffForHumans() }}</span>
        </a>
    @empty
        <p class="text-sm text-gray-500 py-4">No pending recommendations.</p>
    @endforelse
</x-dashboard-modal>

{{-- 3. Outdated Resources: grouped by course, with college and program --}}
<x-dashboard-modal id="m-outdated" title="Outdated Resources"
    :subtitle="$summary['outdated'].' resources older than 5 years, in '.$outdatedBySubject->count().' courses'"
    :proceed="route('librarian.resources.index', ['outdated' => 1])" proceed-label="Proceed to Outdated Resources">
    @forelse ($outdatedBySubject as $items)
        @php $subject = $items->first()->subject; @endphp
        <div class="border-t py-2.5">
            <a href="{{ route('librarian.resources.index', ['subject' => $subject->id]) }}"
               class="flex items-center justify-between gap-3 px-2 -mx-2 rounded hover:bg-csu-50">
                <span>
                    <span class="font-medium">{{ $subject->label }}</span>
                    <span class="block text-xs text-gray-500">{{ $subject->program->college->code }} › {{ $subject->program->code }}</span>
                </span>
                <span class="font-semibold text-red-600 whitespace-nowrap">{{ $items->count() }} outdated ›</span>
            </a>
            <ul class="mt-1 ml-2 text-xs text-gray-600 space-y-0.5">
                @foreach ($items as $r)
                    <li>
                        <a href="{{ route('librarian.resources.edit', $r) }}" class="hover:underline">{{ $r->title }}</a>
                        <span class="text-red-600">({{ $r->publication_year }})</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <p class="text-sm text-gray-500 py-4">No outdated resources.</p>
    @endforelse
</x-dashboard-modal>

{{-- 4. Courses: every course with its college and program --}}
<x-dashboard-modal id="m-subjects" title="All Courses"
    :subtitle="$summary['subjects'].' courses in the classification'"
    :proceed="route('librarian.classifications.index')" proceed-label="Proceed to Classifications">
    @foreach ($subjects->sortBy(fn ($s) => $s['college'].'|'.$s['program'].'|'.$s['subject'])->values() as $s)
        <a href="{{ route('browse.subject', $s['id']) }}"
           class="flex items-center justify-between gap-3 border-t py-2.5 px-2 -mx-2 rounded hover:bg-csu-50">
            <span>
                <span class="font-medium">{{ $s['subject'] }}</span>
                <span class="block text-xs text-gray-500">{{ $s['college'] }} › {{ $s['program'] }}</span>
            </span>
            <span class="text-sm text-gray-600 whitespace-nowrap">{{ $s['resources'] }} {{ Str::plural('resource', $s['resources']) }} ›</span>
        </a>
    @endforeach
</x-dashboard-modal>

{{-- 5. Courses Lacking Resources: fewer than the minimum (5) resources --}}
<x-dashboard-modal id="m-insufficient" title="Courses Lacking Resources"
    :subtitle="$summary['insufficient'].' courses have fewer than '.\App\Models\Subject::MIN_RESOURCES.' resources'"
    :proceed="route('librarian.reports.index')" proceed-label="Proceed to Reports">
    @forelse ($insufficientRows as $s)
        <a href="{{ route('librarian.resources.index', ['subject' => $s['id']]) }}"
           class="flex items-center justify-between gap-3 border-t py-2.5 px-2 -mx-2 rounded hover:bg-csu-50">
            <span>
                <span class="font-medium">{{ $s['subject'] }}</span>
                <span class="block text-xs text-gray-500">{{ $s['college'] }} › {{ $s['program'] }}</span>
            </span>
            <span class="text-sm whitespace-nowrap">
                <span class="font-semibold text-red-600">{{ $s['resources'] }}</span>
                <span class="text-gray-500">of {{ $s['target'] }} needed ›</span>
            </span>
        </a>
    @empty
        <p class="text-sm text-gray-500 py-4">All courses have enough resources.</p>
    @endforelse
</x-dashboard-modal>

<script>
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.body.style.overflow = ''; }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') document.querySelectorAll('.dashboard-modal:not(.hidden)').forEach(m => closeModal(m.id));
    });
</script>
@endsection