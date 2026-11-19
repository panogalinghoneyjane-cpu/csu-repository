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

{{-- Form on the left, History on the right side --}}
<div class="{{ $recommendation->exists ? 'grid lg:grid-cols-3 gap-6 items-start' : '' }}">
<form method="POST" class="bg-white rounded-lg border p-6 space-y-5 {{ $recommendation->exists ? 'lg:col-span-2' : 'max-w-3xl' }}"
      action="{{ $recommendation->exists ? route('faculty.recommendations.update', $recommendation) : route('faculty.recommendations.store') }}"
      data-confirm="Please check the details first. Once the librarian approves it, you can no longer edit or change it."
      data-confirm-title="{{ $recommendation->exists ? 'Resubmit this recommendation?' : 'Submit this recommendation?' }}"
      data-confirm-button="{{ $recommendation->exists ? 'Resubmit' : 'Submit' }}">
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
                  placeholder="How does this support the course outcomes?">{{ old('reason', $recommendation->reason) }}</textarea>
    </div>

    @if ($recommendation->status === 'needs_revision')
        <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
            <label class="block text-sm font-medium mb-1">Revision Comment <span class="text-red-600">*</span></label>
            <textarea name="revision_note" rows="3" required maxlength="1000" class="w-full border rounded px-3 py-2 bg-white"
                      placeholder="e.g. Changed the edition to the 2024 version and updated the link as the librarian requested.">{{ old('revision_note') }}</textarea>
            <p class="text-xs text-gray-600 mt-1">Tell the librarian what you changed or added in this revision. This is saved in the history.</p>
        </div>
    @endif

    <div class="flex gap-3">
        <button class="bg-csu-700 text-white px-5 py-2 rounded hover:bg-csu-800">{{ $recommendation->exists ? 'Resubmit' : 'Submit Recommendation' }}</button>
        <a href="{{ route('faculty.recommendations.index') }}" class="px-5 py-2 rounded border hover:bg-gray-50">Cancel</a>
    </div>
</form>

@if ($recommendation->exists)
    <aside class="lg:sticky lg:top-4">
        @include('partials.recommendation-history', ['class' => 'bg-white rounded-lg border p-5 max-h-[80vh] overflow-y-auto'])
    </aside>
@endif
</div>
@endsection