@extends('layouts.app')
@section('title', 'Librarian Dashboard')

@section('content')
<h1 class="text-2xl font-bold mb-1">Librarian Dashboard</h1>
<p class="text-sm text-gray-500 mb-6">Overview of the repository as of {{ now()->format('F d, Y') }}</p>

<div class="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
    @foreach ([
        ['Active Resources', $stats['resources'], 'text-csu-700', route('librarian.resources.index')],
        ['Pending Recommendations', $stats['pending'], 'text-yellow-600', route('librarian.reviews.index')],
        ['Approved – To Upload', $stats['to_upload'], 'text-blue-600', route('librarian.reviews.index', ['status' => 'approved'])],
        ['Outdated Resources', $stats['outdated'], 'text-red-600', route('librarian.resources.index', ['outdated' => 1])],
        ['Views This Month', $stats['views_month'], 'text-indigo-600', route('librarian.reports.index')],
        ['Searches w/ No Results (30d)', $stats['no_result_searches'], 'text-orange-600', route('librarian.reports.index')],
    ] as [$label, $value, $color, $url])
        <a href="{{ $url }}" class="bg-white rounded-lg border p-4 hover:shadow">
            <p class="text-xs text-gray-500">{{ $label }}</p>
            <p class="text-3xl font-bold {{ $color }}">{{ number_format($value) }}</p>
        </a>
    @endforeach
</div>

@if ($stats['outdated'] > 0)
    <div class="mb-6 rounded border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
        ⚠ {{ $stats['outdated'] }} resources are older than {{ \App\Models\Resource::OUTDATED_AFTER_YEARS }} years.
        <a class="underline font-medium" href="{{ route('librarian.resources.index', ['outdated' => 1]) }}">Review them</a>
        or check the <a class="underline font-medium" href="{{ route('librarian.reports.index') }}">adequacy report</a>.
    </div>
@endif

<div class="grid lg:grid-cols-2 gap-6">
    <section class="bg-white rounded-lg border p-5">
        <div class="flex justify-between items-center mb-3">
            <h2 class="font-semibold">Pending Faculty Recommendations</h2>
            <a href="{{ route('librarian.reviews.index') }}" class="text-sm text-csu-700 hover:underline">View all</a>
        </div>
        @forelse ($pending as $rec)
            <a href="{{ route('librarian.reviews.show', $rec) }}" class="block border-t py-3 hover:bg-gray-50 -mx-2 px-2">
                <p class="font-medium">{{ $rec->title }}</p>
                <p class="text-xs text-gray-500">{{ $rec->faculty->name }} · {{ $rec->subject->label }} · {{ $rec->created_at->diffForHumans() }}</p>
            </a>
        @empty
            <p class="text-sm text-gray-500">No pending recommendations. 🎉</p>
        @endforelse
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

    <section class="bg-white rounded-lg border p-5 lg:col-span-2">
        <h2 class="font-semibold mb-3">Recent Reading Activity</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-gray-500"><tr><th class="py-2">User</th><th>Role</th><th>Resource</th><th>When</th></tr></thead>
                <tbody>
                @forelse ($activity as $log)
                    <tr class="border-t">
                        <td class="py-2">{{ $log->user->name }}</td>
                        <td class="capitalize">{{ $log->user->role }}</td>
                        <td>{{ $log->resource->title }}</td>
                        <td class="text-gray-500">{{ $log->accessed_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-3 text-gray-500">No activity yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
