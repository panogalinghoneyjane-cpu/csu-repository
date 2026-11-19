@extends('layouts.app')
@section('title', $college->code)

@section('content')
<nav class="text-sm text-gray-500 mb-2"><a href="{{ route('browse.colleges') }}" class="hover:underline">Colleges</a> › {{ $college->code }}</nav>
<h1 class="text-2xl font-bold mb-6">{{ $college->name }}</h1>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse ($college->programs as $program)
        <a href="{{ route('browse.program', $program) }}" class="bg-white rounded-lg border p-5 hover:border-csu-600 hover:shadow">
            <p class="text-lg font-bold text-csu-800">{{ $program->code }}</p>
            <p class="text-sm text-gray-600">{{ $program->name }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ $program->subjects_count }} courses</p>
        </a>
    @empty
        <p class="text-gray-500">No programs yet.</p>
    @endforelse
</div>
@endsection
