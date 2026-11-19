@extends('layouts.app')
@section('title', $program->code)

@section('content')
<nav class="text-sm text-gray-500 mb-2">
    <a href="{{ route('browse.colleges') }}" class="hover:underline">Colleges</a> ›
    <a href="{{ route('browse.college', $program->college) }}" class="hover:underline">{{ $program->college->code }}</a> › {{ $program->code }}
</nav>
<h1 class="text-2xl font-bold mb-6">{{ $program->name }}</h1>

<form class="flex flex-wrap items-center justify-between gap-2 mb-4 text-sm">
    <p class="text-gray-500">{{ $program->subjects->count() }} {{ Str::plural('course', $program->subjects->count()) }}</p>
    <label class="flex items-center gap-2">
        <span class="text-gray-600">Sort by</span>
        <select name="sort" onchange="this.form.submit()" class="border rounded px-3 py-1.5 bg-white">
            @foreach ($sorts as $value => [$label])
                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
</form>
<div class="bg-white rounded-lg border divide-y">
    @forelse ($program->subjects as $subject)
        <a href="{{ route('browse.subject', $subject) }}" class="flex items-center justify-between px-5 py-4 hover:bg-gray-50">
            <div>
                <p class="font-semibold">{{ $subject->code }} – {{ $subject->name }}</p>
                @if ($subject->year_level)<p class="text-xs text-gray-500">Year {{ $subject->year_level }}</p>@endif
            </div>
            <span class="text-sm text-gray-500">{{ $subject->active_resources_count }} resources ›</span>
        </a>
    @empty
        <p class="p-5 text-gray-500">No courses yet.</p>
    @endforelse
</div>
@endsection
