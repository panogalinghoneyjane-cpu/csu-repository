@extends('layouts.app')
@section('title', $program->code)

@section('content')
<nav class="text-sm text-gray-500 mb-2">
    <a href="{{ route('browse.colleges') }}" class="hover:underline">Colleges</a> ›
    <a href="{{ route('browse.college', $program->college) }}" class="hover:underline">{{ $program->college->code }}</a> › {{ $program->code }}
</nav>
<h1 class="text-2xl font-bold mb-6">{{ $program->name }}</h1>

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
        <p class="p-5 text-gray-500">No subjects yet.</p>
    @endforelse
</div>
@endsection
