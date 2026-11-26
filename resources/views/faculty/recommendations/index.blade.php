@extends('layouts.app')
@section('title', 'My Recommendations')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">My Recommendations</h1>
        <p class="text-sm text-gray-500">Click a recommendation to see its history.</p>
    </div>
       <a href="{{ route('faculty.recommendations.create') }}" class="self-start shrink-0 text-sm bg-csu-700 text-white px-3 py-1.5 rounded hover:bg-csu-800">+ Recommend a Resource</a>
</div>
{{-- Filter tabs (same colors as the librarian's review tabs). [normal, selected] --}}
@php
    $tabColors = [
        'all'            => ['bg-white text-csu-800 border-csu-700',            'bg-csu-700 text-white border-csu-700'],
        'pending'        => ['bg-yellow-100 text-yellow-800 border-yellow-300', 'bg-yellow-500 text-white border-yellow-500'],
        'approved'       => ['bg-blue-100 text-blue-800 border-blue-300',       'bg-blue-600 text-white border-blue-600'],
        'needs_revision' => ['bg-orange-100 text-orange-800 border-orange-300', 'bg-orange-500 text-white border-orange-500'],
        'rejected'       => ['bg-red-100 text-red-800 border-red-300',          'bg-red-600 text-white border-red-600'],
        'fulfilled'      => ['bg-green-100 text-green-800 border-green-300',    'bg-green-600 text-white border-green-600'],
    ];
@endphp
<div class="flex flex-wrap gap-2 mb-4 text-sm">
    <a href="{{ route('faculty.recommendations.index') }}"
       class="px-3 py-1.5 rounded-full border font-medium hover:shadow-sm {{ $tabColors['all'][$status ? 0 : 1] }}">All</a>
    @foreach (\App\Models\Recommendation::STATUS_LABELS as $value => $label)
        <a href="{{ route('faculty.recommendations.index', ['status' => $value]) }}"
           class="px-3 py-1.5 rounded-full border font-medium hover:shadow-sm {{ $tabColors[$value][$status === $value ? 1 : 0] }}">{{ $label }}</a>
    @endforeach
</div>

<div class="bg-white rounded-lg border overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-600">
            <tr><th class="px-4 py-3">Title</th><th>Course</th><th>Submitted</th><th>Status</th><th>Librarian Remarks</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($recommendations as $rec)
            <tr id="rec-row-{{ $rec->id }}" class="border-t align-top cursor-pointer hover:bg-csu-50" onclick="openHistory('history-{{ $rec->id }}')">
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $rec->title }}</p>
                    <p class="text-xs text-gray-500">{{ $rec->authors }}{{ $rec->publication_year ? ' · '.$rec->publication_year : '' }}</p>
                </td>
                <td class="py-3">{{ $rec->subject->code }}</td>
                <td class="py-3 text-gray-500">{{ $rec->created_at->format('M d, Y') }}</td>
                <td class="py-3">
                    <span class="text-xs px-2 py-1 rounded whitespace-nowrap {{ $rec->statusColor() }}">{{ $rec->statusLabel() }}</span>
                </td>
                <td class="py-3 text-gray-700 max-w-xs">{{ $rec->librarian_remarks ?? '—' }}</td>
                <td class="py-3 pr-4 text-right whitespace-nowrap">
                    @if (in_array($rec->status, ['pending', 'needs_revision']))
                        <a href="{{ route('faculty.recommendations.edit', $rec) }}" onclick="event.stopPropagation()" class="text-csu-700 hover:underline">
                            Revise &amp; Resubmit
                        </a>
                    @elseif ($rec->status === 'fulfilled' && $rec->resource)
                        <a href="{{ route('reader.show', $rec->resource) }}" onclick="event.stopPropagation()" class="text-csu-700 hover:underline">Open</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">
                {{ $status ? 'No recommendations in this tab.' : 'You have not recommended any resources yet.' }}
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $recommendations->links() }}</div>

{{-- ===== History windows (one per recommendation, opened by clicking a row) ===== --}}
@foreach ($recommendations as $rec)
    <div id="history-{{ $rec->id }}" class="history-modal hidden fixed inset-0 z-[90] bg-black/40 flex items-center justify-center p-4"
         onclick="if (event.target === this) closeHistory(this.id)">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[85vh] flex flex-col">
            {{-- Title --}}
            <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-3 border-b">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Recommendation History</p>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $rec->title }}</h2>
                    <p class="text-sm text-gray-500">{{ $rec->subject->code }} · now
                        <span class="text-xs px-2 py-0.5 rounded {{ $rec->statusColor() }}">{{ $rec->statusLabel() }}</span></p>
                </div>
                <button type="button" onclick="closeHistory('history-{{ $rec->id }}')" class="text-2xl leading-none text-gray-400 hover:text-gray-700" title="Close">&times;</button>
            </div>

            {{-- Timeline --}}
            <div class="px-6 py-4 overflow-y-auto">
                @include('partials.recommendation-history', ['recommendation' => $rec])
            </div>

            {{-- Buttons --}}
            <div class="flex flex-wrap items-center gap-3 px-6 py-4 border-t">
                @if (in_array($rec->status, ['pending', 'needs_revision', 'rejected']))
                    <form method="POST" action="{{ route('faculty.recommendations.destroy', $rec) }}" class="mr-auto"
                          data-confirm="“{{ $rec->title }}” and its history will be removed. This cannot be undone."
                          data-confirm-title="Delete this recommendation?" data-confirm-button="Delete" data-confirm-danger
                          data-undo="Recommendation deleted." data-undo-hide=".history-modal" data-undo-also="#rec-row-{{ $rec->id }}">
                        @csrf @method('DELETE')
                        <button class="px-4 py-2 rounded border border-red-300 text-red-600 hover:bg-red-50">Delete</button>
                    </form>
                @else
                    <span class="mr-auto"></span>
                @endif
                <button type="button" onclick="closeHistory('history-{{ $rec->id }}')" class="px-4 py-2 rounded border hover:bg-gray-50">Close</button>
                @if (in_array($rec->status, ['pending', 'needs_revision']))
                    <a href="{{ route('faculty.recommendations.edit', $rec) }}" class="px-4 py-2 rounded bg-csu-700 text-white hover:bg-csu-800">Revise &amp; Resubmit →</a>
                @elseif ($rec->status === 'fulfilled' && $rec->resource)
                    <a href="{{ route('reader.show', $rec->resource) }}" class="px-4 py-2 rounded bg-csu-700 text-white hover:bg-csu-800">Open Resource →</a>
                @endif
            </div>
        </div>
    </div>
@endforeach

<script>
    function openHistory(id) { document.getElementById(id).classList.remove('hidden'); document.body.style.overflow = 'hidden'; }

    // Opened from a bell notification (?open=ID): show that recommendation's history right away
    (function () {
        const id = new URLSearchParams(location.search).get('open');
        if (id && document.getElementById('history-' + id)) openHistory('history-' + id);
    })();
    function closeHistory(id) { document.getElementById(id).classList.add('hidden'); document.body.style.overflow = ''; }
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') document.querySelectorAll('.history-modal:not(.hidden)').forEach(m => closeHistory(m.id));
    });
</script>
@endsection