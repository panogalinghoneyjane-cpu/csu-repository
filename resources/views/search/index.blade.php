@extends('layouts.app')
@section('title', 'Search')

@section('content')
<h1 class="text-2xl font-bold mb-4">Search the Repository</h1>

<div class="mb-6">@include('partials.search-bar', ['withFilters' => true])</div>

@if ($q !== '')
    <div class="grid lg:grid-cols-3 gap-6">
        <section class="lg:col-span-2">
            <h2 class="font-semibold mb-1">{{ $results->count() }} result{{ $results->count() === 1 ? '' : 's' }} for “{{ $q }}”</h2>
            <p class="text-xs text-gray-500 mb-3">Searched titles, authors, keywords, descriptions, links, and the text inside every PDF. Matching courses are listed first.</p>
                       @if ($courses->isNotEmpty())
                <div class="bg-white rounded-lg border p-4 mb-4">
                    <p class="text-sm font-semibold mb-2">Matching courses</p>
                    <div class="divide-y">
                        @foreach ($courses as $course)
                            <a href="{{ route('browse.subject', $course) }}" class="flex items-center justify-between gap-3 py-2 hover:bg-gray-50 -mx-2 px-2 rounded">
                                <span>
                                    <span class="font-medium text-csu-700">{{ \App\Models\Resource::highlight($course->code.' – '.$course->name, $q) }}</span>
                                    <span class="block text-xs text-gray-500">{{ $course->program->college->code }} › {{ $course->program->code }}</span>
                                </span>
                                <span class="text-xs whitespace-nowrap {{ $course->active_resources_count ? 'text-gray-600' : 'text-red-600' }}">
                                    {{ $course->active_resources_count ?: 'No' }} resource{{ $course->active_resources_count == 1 ? '' : 's' }} yet
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="space-y-3">
                @forelse ($results as $resource)
                    @include('partials.resource-card', ['resource' => $resource, 'snippet' => $resource->contentSnippet($q), 'term' => $q])
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
                <p class="text-xs text-gray-600 mb-3">Related materials based on your keywords, course classification, and what other readers opened.</p>
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
