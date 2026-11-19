@extends('layouts.app')
@section('title', $class->exists ? 'Edit Class' : 'New Class')

@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $class->exists ? 'Edit Class' : 'Create a Class' }}</h1>
<p class="text-sm text-gray-500 mb-6">Choose the course you handle and your section. After creating it, you can add resources and share the class link with your students.</p>

<form method="POST" action="{{ $class->exists ? route('faculty.classes.update', $class) : route('faculty.classes.store') }}"
      class="bg-white rounded-lg border p-6 space-y-5 max-w-3xl">
    @csrf
    @if ($class->exists) @method('PUT') @endif

    @include('partials.subject-select', ['colleges' => $colleges, 'selected' => old('subject_id', $class->subject_id)])

    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Section <span class="text-red-600">*</span></label>
            <input name="section" value="{{ old('section', $class->section) }}" required maxlength="50" placeholder="e.g. PRS1 or BSIT 2-A" class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">School Year</label>
            <input name="school_year" value="{{ old('school_year', $class->school_year) }}" maxlength="20" placeholder="2026-2027" class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Semester</label>
            <select name="semester" class="w-full border rounded px-3 py-2">
                <option value="">—</option>
                @foreach (\App\Models\CourseClass::SEMESTERS as $sem)
                    <option @selected(old('semester', $class->semester) === $sem)>{{ $sem }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Description / Instructions for students</label>
        <textarea name="description" rows="3" maxlength="2000" placeholder="e.g. Review these materials before each lesson." class="w-full border rounded px-3 py-2">{{ old('description', $class->description) }}</textarea>
    </div>

    @if ($class->exists)
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_open" value="1" @checked(old('is_open', $class->is_open))>
            Allow new students to join with the link or code
        </label>
    @endif

    <div class="flex gap-3">
        <button class="bg-csu-700 text-white px-5 py-2 rounded hover:bg-csu-800">{{ $class->exists ? 'Save Changes' : 'Create Class' }}</button>
        <a href="{{ $class->exists ? route('classes.show', $class) : route('classes.index') }}" class="px-5 py-2 rounded border hover:bg-gray-50">Cancel</a>
    </div>
</form>
@endsection
