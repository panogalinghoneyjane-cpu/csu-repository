@extends('layouts.app')
@section('title', 'Faculty Dashboard')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Welcome, {{ auth()->user()->name }}</h1>
        <p class="text-sm text-gray-500">Search the repository or recommend resources for the library to acquire.</p>
    </div>
</div>

@php $byStatus = $myRecommendations->groupBy('status'); @endphp

{{-- ===== Boxes (click to see the list) ===== --}}
@php
    $boxColors = [
        'pending' => 'text-amber-600',
        'approved' => 'text-blue-600',
        'rejected' => 'text-red-600',
        'needs_revision' => 'text-orange-600',
        'fulfilled' => 'text-csu-700',
    ];
@endphp
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
    @foreach (\App\Models\Recommendation::STATUS_LABELS as $status => $label)
        <button type="button" onclick="openModal('rec-{{ $status }}')"
                class="text-left bg-white rounded-lg border p-4 hover:border-csu-600 hover:shadow-sm transition">
            <p class="text-sm text-gray-500">{{ $label }}</p>
            <p class="text-3xl font-bold {{ $boxColors[$status] }}">{{ $counts[$status] ?? 0 }}</p>
        </button>
    @endforeach
</div>

{{-- ===== Pop-up for each box ===== --}}
@foreach (\App\Models\Recommendation::STATUS_LABELS as $status => $label)
    @php $items = $byStatus[$status] ?? collect(); @endphp
    <x-dashboard-modal :id="'rec-'.$status" :title="$label"
        :subtitle="$items->count().' '.Str::plural('recommendation', $items->count())"
        :proceed="route('faculty.recommendations.index', ['status' => $status])" proceed-label="Proceed to My Recommendations">
        @forelse ($items as $rec)
            @php
                $link = match (true) {
                    in_array($rec->status, ['pending', 'needs_revision']) => route('faculty.recommendations.edit', $rec),
                    $rec->status === 'fulfilled' && $rec->resource !== null => route('reader.show', $rec->resource),
                    default => route('faculty.recommendations.index', ['status' => $status]),
                };
            @endphp
            <a href="{{ $link }}" class="block border-t py-2.5 px-2 -mx-2 rounded hover:bg-csu-50">
                <span class="flex items-start justify-between gap-3">
                    <span class="font-medium">{{ $rec->title }}</span>
                    @if (in_array($rec->status, ['pending', 'needs_revision']))
                        <span class="text-xs text-csu-700 whitespace-nowrap">Revise &amp; Resubmit ›</span>
                    @endif
                </span>
                <span class="block text-xs text-gray-500">
                    {{ $rec->subject->label }} · {{ $rec->subject->program->college->code }} › {{ $rec->subject->program->code }}
                    · {{ $rec->created_at->format('M d, Y') }}
                </span>
                @if ($rec->librarian_remarks)
                    <span class="block text-xs text-gray-700 mt-0.5">Librarian: “{{ $rec->librarian_remarks }}”</span>
                @endif
            </a>
        @empty
            <p class="text-sm text-gray-500 py-6 text-center">No recommendations are {{ strtolower($label) }}.</p>
        @endforelse
    </x-dashboard-modal>
@endforeach

<script>
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.body.style.overflow = ''; }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') document.querySelectorAll('.dashboard-modal:not(.hidden)').forEach(m => closeModal(m.id));
    });
</script>

{{-- ===== Announcements (left, as wide as its poster) + Newly Added (right, fills the rest) ===== --}}
<div class="flex flex-col lg:flex-row gap-6 mb-8">
    @include('partials.announcements')

    <section class="flex-1 min-w-0">
        <h2 class="text-lg font-semibold mb-3">Newly Added to the Repository</h2>
        {{-- as many columns as fit (each card at least 15rem wide), so cards are never squeezed --}}
        <div class="grid grid-cols-[repeat(auto-fill,minmax(15rem,1fr))] gap-4">
            @foreach ($recent as $resource)
                @include('partials.resource-card', ['resource' => $resource])
            @endforeach
        </div>
    </section>
</div>
@endsection
