@extends('layouts.app')
@section('title', 'Review Recommendations')

@section('content')
<h1 class="text-2xl font-bold mb-4">Faculty Resource Recommendations</h1>

<div class="flex flex-wrap gap-2 mb-4 text-sm">
    @foreach (['pending' => 'Pending', 'approved' => 'Approved – To Upload', 'needs_revision' => 'Returned', 'rejected' => 'Rejected', 'fulfilled' => 'Added', 'all' => 'All'] as $key => $label)
        <a href="{{ route('librarian.reviews.index', ['status' => $key]) }}"
           class="px-3 py-1.5 rounded-full border {{ $status === $key ? 'bg-csu-700 text-white border-csu-700' : 'bg-white hover:bg-gray-50' }}">
            {{ $label }}{{ $key !== 'all' ? ' ('.($counts[$key] ?? 0).')' : '' }}
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
