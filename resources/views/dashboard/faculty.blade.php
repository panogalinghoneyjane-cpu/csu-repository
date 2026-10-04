@extends('layouts.app')
@section('title', 'Faculty Dashboard')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Welcome, {{ auth()->user()->name }}</h1>
        <p class="text-sm text-gray-500">Search the repository or recommend resources for the library to acquire.</p>
    </div>
    <a href="{{ route('faculty.recommendations.create') }}" class="bg-csu-700 text-white px-4 py-2 rounded hover:bg-csu-800">+ Recommend a Resource</a>
</div>

<div class="mb-6">@include('partials.search-bar')</div>

<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    @foreach (\App\Models\Recommendation::STATUS_LABELS as $status => $label)
        <a href="{{ route('faculty.recommendations.index') }}" class="bg-white rounded-lg border p-3">
            <p class="text-xs text-gray-500">{{ $label }}</p>
            <p class="text-2xl font-bold">{{ $counts[$status] ?? 0 }}</p>
        </a>
    @endforeach
</div>

<section class="bg-white rounded-lg border p-5 mb-6">
    <h2 class="font-semibold mb-3">Updates on Your Recommendations</h2>
    @forelse ($updates as $rec)
        <div class="border-t py-3 flex flex-wrap items-start justify-between gap-2">
            <div>
                <p class="font-medium">{{ $rec->title }}</p>
                <p class="text-xs text-gray-500">{{ $rec->subject->label }} · reviewed {{ $rec->reviewed_at->diffForHumans() }}</p>
                @if ($rec->librarian_remarks)
                    <p class="text-sm text-gray-700 mt-1">Librarian: “{{ $rec->librarian_remarks }}”</p>
                @endif
            </div>
            <span class="text-xs px-2 py-1 rounded {{ $rec->statusColor() }}">{{ $rec->statusLabel() }}</span>
        </div>
    @empty
        <p class="text-sm text-gray-500">No updates yet.</p>
    @endforelse
</section>

<h2 class="text-lg font-semibold mb-3">Newly Added to the Repository</h2>
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    @foreach ($recent as $resource)
        @include('partials.resource-card', ['resource' => $resource])
    @endforeach
</div>

<h2 class="text-lg font-semibold mb-3">Browse by College</h2>
@include('partials.college-grid')
@endsection
