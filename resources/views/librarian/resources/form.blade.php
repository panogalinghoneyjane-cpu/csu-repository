@extends('layouts.app')
@section('title', $resource->exists ? 'Edit Resource' : 'Upload Resource')

@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $resource->exists ? 'Edit Resource' : 'Upload & Classify Resource' }}</h1>
<p class="text-sm text-gray-500 mb-6">Files are stored privately and can only be viewed through the protected reader.</p>

@if ($recommendation && ! $resource->exists)
    <div class="mb-4 rounded border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        Fulfilling a faculty recommendation by <strong>{{ $recommendation->faculty->name }}</strong>: “{{ $recommendation->title }}”.
        The details below were pre-filled from the recommendation.
    </div>
@endif

<form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg border p-6 space-y-5 max-w-3xl"
      action="{{ $resource->exists ? route('librarian.resources.update', $resource) : route('librarian.resources.store') }}">
    @csrf
    @if ($resource->exists) @method('PUT') @endif
    <input type="hidden" name="recommendation_id" value="{{ old('recommendation_id', $resource->recommendation_id) }}">

    <fieldset class="space-y-2">
        <legend class="font-semibold mb-2">Classification</legend>
        @include('partials.subject-select', ['colleges' => $colleges, 'selected' => old('subject_id', $resource->subject_id)])
    </fieldset>

    <div>
        <label class="block text-sm font-medium mb-1">Title <span class="text-red-600">*</span></label>
        <input name="title" value="{{ old('title', $resource->title) }}" required class="w-full border rounded px-3 py-2">
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Author(s) <span class="text-red-600">*</span></label>
            <input name="authors" value="{{ old('authors', $resource->authors) }}" required class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Keywords <span class="text-gray-400">(comma-separated)</span></label>
            <input name="keywords" value="{{ old('keywords', $resource->keywords) }}" placeholder="web, laravel, php" class="w-full border rounded px-3 py-2">
        </div>
    </div>
    <div class="grid sm:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Publication Year <span class="text-red-600">*</span></label>
            <input type="number" name="publication_year" value="{{ old('publication_year', $resource->publication_year) }}" required class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Type <span class="text-red-600">*</span></label>
            <select name="resource_type" class="w-full border rounded px-3 py-2">
                @foreach ($types as $t)<option @selected(old('resource_type', $resource->resource_type) === $t)>{{ $t }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Academic Year</label>
            <input name="academic_year" value="{{ old('academic_year', $resource->academic_year ?? (now()->month >= 8 ? now()->year.'-'.(now()->year + 1) : (now()->year - 1).'-'.now()->year)) }}" class="w-full border rounded px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Status</label>
            <select name="status" class="w-full border rounded px-3 py-2">
                <option value="active" @selected(old('status', $resource->status ?? 'active') === 'active')>Active</option>
                <option value="archived" @selected(old('status', $resource->status) === 'archived')>Archived</option>
            </select>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Description</label>
        <textarea name="description" rows="3" class="w-full border rounded px-3 py-2">{{ old('description', $resource->description) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">PDF File @unless ($resource->exists)<span class="text-red-600">*</span>@endunless</label>
        <input type="file" name="file" accept="application/pdf" @unless ($resource->exists) required @endunless class="w-full border rounded px-3 py-2 bg-gray-50">
        <p class="text-xs text-gray-500 mt-1">PDF only, up to 50 MB. @if ($resource->exists) Leave empty to keep the current file. @endif</p>
    </div>

    <div class="flex gap-3">
        <button class="bg-csu-700 text-white px-5 py-2 rounded hover:bg-csu-800">{{ $resource->exists ? 'Save Changes' : 'Upload to Repository' }}</button>
        <a href="{{ route('librarian.resources.index') }}" class="px-5 py-2 rounded border hover:bg-gray-50">Cancel</a>
    </div>
</form>
@endsection
