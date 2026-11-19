@extends('layouts.app')
@section('title', $subject->code)

@section('content')
<nav class="text-sm text-gray-500 mb-2">
    <a href="{{ route('browse.colleges') }}" class="hover:underline">Colleges</a> ›
    <a href="{{ route('browse.college', $subject->program->college) }}" class="hover:underline">{{ $subject->program->college->code }}</a> ›
    <a href="{{ route('browse.program', $subject->program) }}" class="hover:underline">{{ $subject->program->code }}</a> › {{ $subject->code }}
</nav>
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold">{{ $subject->label }}</h1>
    @if (auth()->user()->isLibrarian())
        <a href="{{ route('librarian.resources.create') }}" class="bg-csu-700 text-white px-4 py-2 rounded text-sm hover:bg-csu-800">+ Add Resource</a>
    @elseif (auth()->user()->isFaculty())
        <a href="{{ route('faculty.recommendations.create') }}" class="bg-csu-700 text-white px-4 py-2 rounded text-sm hover:bg-csu-800">+ Recommend a Resource</a>
    @endif
</div>
<form class="flex flex-wrap items-center justify-between gap-2 mb-4 text-sm">
    <p class="text-gray-500">{{ $resources->total() }} {{ Str::plural('resource', $resources->total()) }}</p>
    <label class="flex items-center gap-2">
        <span class="text-gray-600">Sort by</span>
        <select name="sort" onchange="this.form.submit()" class="border rounded px-3 py-1.5 bg-white">
            @foreach ($sorts as $value => [$label])
                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
</form>

<div class="grid md:grid-cols-2 gap-4">
    @forelse ($resources as $resource)
        @include('partials.resource-card', ['resource' => $resource])
    @empty
        <p class="text-gray-500">No resources for this course yet.</p>
    @endforelse
</div>
<div class="mt-6">{{ $resources->links() }}</div>
@endsection
