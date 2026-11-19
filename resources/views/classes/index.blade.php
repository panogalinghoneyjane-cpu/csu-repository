@extends('layouts.app')
@section('title', 'My Classes')

@section('content')
@php $user = auth()->user(); @endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-1">
    <h1 class="text-2xl font-bold">{{ $user->isLibrarian() ? 'All Classes' : 'My Classes' }}</h1>
    @if ($user->isFaculty())
                <a href="{{ route('faculty.classes.create') }}" class="self-start shrink-0 text-sm bg-csu-700 text-white px-3 py-1.5 rounded hover:bg-csu-800">+ New Class</a>
    @endif
</div>
<p class="text-sm text-gray-500 mb-6">
    @if ($user->isFaculty())
        Create a class for each course and section you handle, add resources from the repository, then share the class link with your students.
    @elseif ($user->isStudent())
        Classes your teachers shared with you. Open a class to see the resources recommended for that course.
    @else
        Classes created by faculty members, with their resources and students.
    @endif
</p>

{{-- Student: join with a class code --}}
@if ($user->isStudent())
    <form method="POST" action="{{ route('classes.join-code') }}" class="bg-white rounded-lg border p-4 mb-6 flex flex-wrap items-end gap-3">
        @csrf
        <label class="block">
            <span class="block text-sm font-medium mb-1">Have a class code?</span>
            <input name="code" value="{{ old('code') }}" placeholder="e.g. K7Q2MX" maxlength="20" required
                   class="border rounded px-3 py-2 uppercase tracking-widest w-48">
        </label>
        <button class="bg-csu-700 text-white px-4 py-2 rounded hover:bg-csu-800">Join Class</button>
        <p class="text-xs text-gray-500 w-full">Or simply open the class link your teacher sent you.</p>
    </form>
@endif

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse ($classes as $class)
        <div class="bg-white rounded-lg border overflow-hidden flex flex-col">
            <a href="{{ route('classes.show', $class) }}" class="block bg-csu-700 text-white px-4 py-3 hover:bg-csu-800">
                <p class="font-bold text-lg leading-tight">{{ $class->title }}</p>
                <p class="text-xs text-csu-100 truncate">{{ $class->subject->name }}</p>
            </a>
            <div class="p-4 text-sm flex-1 space-y-1">
                @unless ($user->isFaculty())
                    <p class="text-gray-700">{{ $class->faculty->name }}</p>
                @endunless
                <p class="text-gray-500">{{ $class->semester ?? '' }}{{ $class->semester && $class->school_year ? ' · ' : '' }}{{ $class->school_year }}</p>
                <p class="text-gray-500">{{ $class->resources_count }} {{ \Illuminate\Support\Str::plural('resource', $class->resources_count) }}
                    @unless ($user->isStudent()) · {{ $class->students_count }} {{ \Illuminate\Support\Str::plural('student', $class->students_count) }} @endunless
                </p>
                @if (! $class->is_open)
                    <span class="inline-block text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded">Closed to new students</span>
                @endif
            </div>
            <div class="px-4 py-3 border-t flex items-center justify-between gap-2 text-sm">
                <a href="{{ route('classes.show', $class) }}" class="text-csu-700 font-semibold hover:underline">Open class ›</a>
                @if ($user->isFaculty())
                    <button type="button" class="text-gray-600 hover:text-csu-700"
                                                       onclick="navigator.clipboard.writeText('{{ $class->shareUrl() }}'); this.textContent='Link copied ✓'">Copy link</button>
                @endif
            </div>
        </div>
    @empty
        <div class="sm:col-span-2 lg:col-span-3 bg-white rounded-lg border p-8 text-center text-gray-600">
            @if ($user->isFaculty())
                <p class="font-medium">You have no classes yet.</p>
                <p class="text-sm mt-1">Click <strong>+ New Class</strong> to create one for a course you handle.</p>
            @elseif ($user->isStudent())
                <p class="font-medium">No saved classes yet.</p>
                <p class="text-sm mt-1">Open the class link from your teacher, or enter the class code above.</p>
            @else
                <p class="font-medium">No classes have been created yet.</p>
            @endif
        </div>
    @endforelse
</div>
@endsection
