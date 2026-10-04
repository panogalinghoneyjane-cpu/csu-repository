@extends('layouts.app')
@section('title', 'My Recommendations')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold">My Recommendations</h1>
    <a href="{{ route('faculty.recommendations.create') }}" class="bg-csu-700 text-white px-4 py-2 rounded hover:bg-csu-800">+ Recommend a Resource</a>
</div>

<div class="bg-white rounded-lg border overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-600">
            <tr><th class="px-4 py-3">Title</th><th>Subject</th><th>Submitted</th><th>Status</th><th>Librarian Remarks</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($recommendations as $rec)
            <tr class="border-t align-top">
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $rec->title }}</p>
                    <p class="text-xs text-gray-500">{{ $rec->authors }}{{ $rec->publication_year ? ' · '.$rec->publication_year : '' }}</p>
                </td>
                <td class="py-3">{{ $rec->subject->code }}</td>
                <td class="py-3 text-gray-500">{{ $rec->created_at->format('M d, Y') }}</td>
                <td class="py-3"><span class="text-xs px-2 py-1 rounded whitespace-nowrap {{ $rec->statusColor() }}">{{ $rec->statusLabel() }}</span></td>
                <td class="py-3 text-gray-700 max-w-xs">{{ $rec->librarian_remarks ?? '—' }}</td>
                <td class="py-3 pr-4 text-right whitespace-nowrap">
                    @if (in_array($rec->status, ['pending', 'needs_revision']))
                        <a href="{{ route('faculty.recommendations.edit', $rec) }}" class="text-csu-700 hover:underline">
                            {{ $rec->status === 'needs_revision' ? 'Revise & Resubmit' : 'Edit' }}
                        </a>
                    @elseif ($rec->status === 'fulfilled' && $rec->resource)
                        <a href="{{ route('reader.show', $rec->resource) }}" class="text-csu-700 hover:underline">Open</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">You have not recommended any resources yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $recommendations->links() }}</div>
@endsection
