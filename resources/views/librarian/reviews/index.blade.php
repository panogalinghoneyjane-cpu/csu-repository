@extends('layouts.app')
@section('title', 'Review Recommendations')

@section('content')
<h1 class="text-2xl font-bold mb-4">Faculty Resource Recommendations</h1>

@php
    // Each tab has the same color as its status label. [normal, selected]
    $tabColors = [
        'all'            => ['bg-white text-csu-800 border-csu-700',          'bg-csu-700 text-white border-csu-700'],
        'pending'        => ['bg-yellow-100 text-yellow-800 border-yellow-300', 'bg-yellow-500 text-white border-yellow-500'],
        'approved'       => ['bg-blue-100 text-blue-800 border-blue-300',       'bg-blue-600 text-white border-blue-600'],
        'needs_revision' => ['bg-orange-100 text-orange-800 border-orange-300', 'bg-orange-500 text-white border-orange-500'],
        'rejected'       => ['bg-red-100 text-red-800 border-red-300',          'bg-red-600 text-white border-red-600'],
        'fulfilled'      => ['bg-green-100 text-green-800 border-green-300',    'bg-green-600 text-white border-green-600'],
    ];
@endphp
<div class="flex flex-wrap gap-2 mb-4 text-sm">
    @foreach (['all' => 'All', 'pending' => 'Pending', 'approved' => 'For Purchase', 'needs_revision' => 'Returned', 'rejected' => 'Rejected', 'fulfilled' => 'Added to Repository'] as $key => $label)
        <a href="{{ route('librarian.reviews.index', ['status' => $key]) }}"
           class="px-3 py-1.5 rounded-full border font-medium hover:shadow-sm {{ $tabColors[$key][$status === $key ? 1 : 0] }}">
            {{ $label }} ({{ $key === 'all' ? $counts->sum() : ($counts[$key] ?? 0) }})
        </a>
    @endforeach
</div>

<div class="space-y-3">
    @forelse ($recommendations as $rec)
        <div class="bg-white rounded-lg border p-4 flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="font-semibold">{{ $rec->title }}</p>
                <p class="text-sm text-gray-600">{{ $rec->authors }}{{ $rec->publication_year ? ' · '.$rec->publication_year : '' }} · {{ $rec->resource_type }}</p>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $rec->subject->program->college->code }} › {{ $rec->subject->program->code }} › {{ $rec->subject->label }}
                    · Recommended by {{ $rec->faculty->name }} · {{ $rec->created_at->diffForHumans() }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-1 rounded {{ $rec->statusColor() }}">{{ $rec->statusLabel() }}</span>
                @if ($rec->status === 'approved')
                    <a href="{{ route('librarian.resources.create', ['recommendation' => $rec->id]) }}" class="text-sm bg-blue-600 text-white px-3 py-1.5 rounded hover:bg-blue-700">Upload</a>
                @endif
                <a href="{{ route('librarian.reviews.show', $rec) }}" class="text-sm bg-csu-700 text-white px-3 py-1.5 rounded hover:bg-csu-800">
                    {{ $rec->status === 'pending' ? 'Review' : 'View' }}
                </a>
            </div>
        </div>
    @empty
        <p class="text-gray-500 bg-white rounded-lg border p-6 text-center">Nothing here.</p>
    @endforelse
</div>
<div class="mt-4">{{ $recommendations->links() }}</div>
@endsection
