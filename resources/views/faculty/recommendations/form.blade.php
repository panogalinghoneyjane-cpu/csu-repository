@extends('layouts.app')
@section('title', $recommendation->exists ? 'Edit Recommendation' : 'Recommend a Resource')

@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $recommendation->exists ? 'Edit Recommendation' : 'Recommend a Resource' }}</h1>
<p class="text-sm text-gray-500 mb-6">Suggest a book, journal, or other material for the library to acquire. The librarian will review it, and once approved, acquire and upload it to the repository.</p>

@if ($recommendation->status === 'needs_revision' && $recommendation->librarian_remarks)
    <div class="mb-4 rounded border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-800">
        <strong>Librarian's note:</strong> {{ $recommendation->librarian_remarks }}
    </div>
@endif

<form method="POST" class="bg-white rounded-lg border p-6 space-y-5 max-w-3xl"
      action="{{ $recommendation->exists ? route('faculty.recommendations.update', $recommendation) : route('faculty.recommendations.store') }}">
    @csrf
    @if ($recommendation->exists) @method('PUT') @endif

    @include('partials.subject-select', ['colleges' => $colleges, 'selected' => old('subject_id', $recommendation->subject_id)])

    <div>
        <label class="block text-sm font-medium mb-1">Title <span class="text-red-600">*</span></label>
        <input name="title" value="{{ old('title', $recommendation->title) }}" required class="w-full border rounded px-3 py-2">
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Author(s) <span class="text-red-600">*</span></label>
            <input name="authors" value="{{ old('authors', $recommendation->authors) }}" required class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Publisher</label>
            <input name="publisher" value="{{ old('publisher', $recommendation->publisher) }}" class="w-full border rounded px-3 py-2">
        </div>
    </div>
    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Edition</label>
            <input name="edition" value="{{ old('edition', $recommendation->edition) }}" class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Publication Year</label>
            <input type="number" name="publication_year" value="{{ old('publication_year', $recommendation->publication_year) }}" class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Resource Type <span class="text-red-600">*</span></label>
            <select name="resource_type" class="w-full border rounded px-3 py-2">
                @foreach ($types as $t)
                    <option @selected(old('resource_type', $recommendation->resource_type) === $t)>{{ $t }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Link / Source (optional)</label>
        <input type="url" name="source_link" value="{{ old('source_link', $recommendation->source_link) }}" placeholder="https://..." class="w-full border rounded px-3 py-2">
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Reason for Recommendation <span class="text-red-600">*</span></label>
        <textarea name="reason" rows="4" required class="w-full border rounded px-3 py-2"
                  placeholder="How does this support the subject's course outcomes?">{{ old('reason', $recommendation->reason) }}</textarea>
    </div>

    <div class="flex gap-3">
        <button class="bg-csu-700 text-white px-5 py-2 rounded hover:bg-csu-800">{{ $recommendation->exists ? 'Resubmit' : 'Submit Recommendation' }}</button>
        <a href="{{ route('faculty.recommendations.index') }}" class="px-5 py-2 rounded border hover:bg-gray-50">Cancel</a>
    </div>
</form>
@endsection
