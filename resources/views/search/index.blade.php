@extends('layouts.app')
@section('title', 'Search')

@section('content')
<h1 class="text-2xl font-bold mb-4">Search the Repository</h1>

<form action="{{ route('search') }}" method="GET" class="bg-white rounded-lg border p-4 mb-6 space-y-3">
    <div class="flex gap-2">
        <input type="text" name="q" value="{{ $q }}" placeholder="Search by title, author, keyword, or subject..." autofocus
               class="flex-1 border rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-csu-600">
        <button class="bg-csu-700 text-white px-5 rounded-lg hover:bg-csu-800">Search</button>
    </div>
    <div class="grid sm:grid-cols-3 gap-3 text-sm">
        <select name="college" class="border rounded px-3 py-2">
            <option value="">All colleges</option>
            @foreach ($colleges as $c)
                <option value="{{ $c->id }}" @selected(($filters['college'] ?? '') == $c->id)>{{ $c->code }}</option>
            @endforeach
        </select>
        <select name="type" class="border rounded px-3 py-2">
            <option value="">All resource types</option>
            @foreach ($types as $t)
                <option @selected(($filters['type'] ?? '') === $t)>{{ $t }}</option>
            @endforeach
        </select>
        <select name="year" class="border rounded px-3 py-2">
            <option value="">Any publication year</option>
            @foreach ([1, 3, 5, 10] as $n)
                <option value="{{ now()->year - $n }}" @selected(($filters['year'] ?? '') == now()->year - $n)>Last {{ $n }} year{{ $n > 1 ? 's' : '' }}</option>
            @endforeach
        </select>
    </div>
</form>

@if ($q !== '')
    <div class="grid lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2">
            <h2 class="font-semibold mb-3">{{ $results->count() }} result{{ $results->count() === 1 ? '' : 's' }} for “{{ $q }}”</h2>
            <div class="space-y-3">
                @forelse ($results as $resource)
                    @include('partials.resource-card', ['resource' => $resource])
                @empty
                    <div class="bg-white rounded-lg border p-6 text-center text-gray-600">
                        <p class="font-medium">No resources matched your search.</p>
                        <p class="text-sm mt-1">Try other keywords, or check the recommended resources.</p>
                        @if (auth()->user()->isFaculty())
                            <a href="{{ route('faculty.recommendations.create') }}" class="inline-block mt-3 text-sm text-csu-700 underline">Recommend that the library acquire it</a>
                        @endif
                    </div>
                @endforelse
            </div>
        </section>

        <aside>
            <div class="bg-csu-50 border border-csu-100 rounded-lg p-4 lg:sticky lg:top-4">
                <h2 class="font-semibold text-csu-800 mb-1">Recommended Resources</h2>
                <p class="text-xs text-gray-600 mb-3">Related materials based on your keywords, subject classification, and what other readers opened.</p>
                <div class="space-y-3">
                    @forelse ($related as $resource)
                        @include('partials.resource-card', ['resource' => $resource, 'reason' => $resource->recommend_reason])
                    @empty
                        <p class="text-sm text-gray-500">No related resources found.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>
@else
    <p class="text-gray-500">Type a keyword above to start searching.</p>
@endif
@endsection
