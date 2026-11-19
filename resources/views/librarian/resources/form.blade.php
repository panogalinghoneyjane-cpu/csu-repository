@extends('layouts.app')
@section('title', $resource->exists ? 'Edit Resource' : 'Upload Resource')

@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $resource->exists ? 'Edit Resource' : 'Add & Classify Resource' }}</h1>
<p class="text-sm text-gray-500 mb-6">Upload a PDF (stored privately, view-only) or add a link to an online resource.</p>

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
    <div>
        <label class="block text-sm font-medium mb-1">Author(s) <span class="text-red-600">*</span></label>
        <input name="authors" value="{{ old('authors', $resource->authors) }}" required class="w-full border rounded px-3 py-2">
    </div>
    <div class="grid sm:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Publication Year <span class="text-red-600">*</span></label>
            <input type="number" name="publication_year" min="1900" max="{{ now()->year + 1 }}" value="{{ old('publication_year', $resource->publication_year) }}" required class="w-full border rounded px-3 py-2">
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
        <input type="hidden" name="status" value="active">
        @if ($resource->exists)
            {{-- Only when editing: the librarian can hide the resource --}}
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm font-medium pb-2">
                    <input type="checkbox" name="status" value="archived" @checked(old('status', $resource->status) === 'archived')>
                    Hide this resource
                </label>
            </div>
        @endif
    </div>
    <div>
        <label class="block text-sm font-medium mb-1">Description</label>
        <textarea name="description" rows="3" class="w-full border rounded px-3 py-2">{{ old('description', $resource->description) }}</textarea>
    </div>

    
    {{-- ===== Physical copy in the library ===== --}}
    <div class="rounded-lg border border-csu-100 bg-csu-50 p-4">
        <label class="flex items-center gap-2 text-sm font-medium">
            <input type="hidden" name="has_physical_copy" value="0">
            <input type="checkbox" name="has_physical_copy" value="1" id="has-physical"
                   @checked(old('has_physical_copy', $resource->has_physical_copy))>
            Available as a physical book in the library
        </label>
        <p class="text-xs text-gray-600 mt-1 ml-6">Check this if students can also borrow or read the printed copy at the University Library.</p>

        <div id="physical-details" class="grid sm:grid-cols-2 gap-4 mt-3 ml-6">
            <div>
                <label class="block text-sm font-medium mb-1">Call Number</label>
                <input name="call_number" value="{{ old('call_number', $resource->call_number) }}" maxlength="100"
                       placeholder="e.g. QA76.9 .D35 O63 2023" class="w-full border rounded px-3 py-2 bg-white">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Shelf Location</label>
                <input name="shelf_location" value="{{ old('shelf_location', $resource->shelf_location) }}" maxlength="150"
                       placeholder="e.g. 2nd Floor, Circulation Section, Shelf B" class="w-full border rounded px-3 py-2 bg-white">
            </div>
        </div>
    </div>
    <script>
        // Show Call Number and Shelf Location only when the box is checked
        (function () {
            const box = document.getElementById('has-physical');
            const details = document.getElementById('physical-details');
            const toggle = () => details.classList.toggle('hidden', !box.checked);
            box.addEventListener('change', toggle);
            toggle();
        })();
    </script>
    {{-- ===== Resource source: uploaded PDF or online link ===== --}}
    @php
        // Default: keep what the resource already is; for a new one use PDF unless a link was pre-filled
        $sourceType = old('source_type', $resource->exists
            ? ($resource->isLink() ? 'link' : 'file')
            : ($resource->external_url ? 'link' : 'file'));
    @endphp
    <div class="space-y-4">
        <div class="sm:w-1/2">
            <label for="source_type" class="block text-sm font-medium mb-1">Resource Source <span class="text-red-600">*</span></label>
            <select id="source_type" name="source_type" class="w-full border rounded px-3 py-2">
                <option value="file" @selected($sourceType === 'file')>Upload PDF file</option>
                <option value="link" @selected($sourceType === 'link')>Online link (URL)</option>
            </select>
        </div>

        {{-- PDF upload --}}
        <div id="source-file" class="{{ $sourceType === 'file' ? '' : 'hidden' }}">
            <label class="block text-sm font-medium mb-1">PDF File @unless ($resource->file_path)<span class="text-red-600">*</span>@endunless</label>
                       @if ($resource->file_path)
                {{-- The PDF already saved for this resource --}}
                <div id="current-file" class="flex flex-wrap items-center justify-between gap-2 rounded border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">
                    <span>📄 A PDF file is already saved for this resource.</span>
                    <span class="flex gap-4">
                        <a href="{{ route('reader.show', $resource) }}" target="_blank" class="font-medium underline">View current file</a>
                        <button type="button" onclick="document.getElementById('pdf-input').click()" class="font-medium underline">Change file</button>
                    </span>
                </div>
                {{-- Shown after choosing a new PDF --}}
                <div id="new-file" class="hidden flex flex-wrap items-center justify-between gap-2 rounded border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-800">
                    <span>📄 <strong id="new-file-name"></strong> will replace the current file when you click Save Changes.</span>
                    <button type="button" onclick="undoNewFile()" class="font-medium underline">Undo</button>
                </div>
            @endif
            <input type="file" id="pdf-input" name="file" accept="application/pdf"
                   class="{{ $resource->file_path ? 'hidden' : '' }} w-full border rounded px-3 py-2 bg-gray-50">
            <script>
                document.getElementById('pdf-input').addEventListener('change', function () {
                    const current = document.getElementById('current-file');
                    if (!current) return;   // new resource: the normal file box is shown
                    const chosen = this.files.length > 0;
                    if (chosen) document.getElementById('new-file-name').textContent = this.files[0].name;
                    current.classList.toggle('hidden', chosen);
                    document.getElementById('new-file').classList.toggle('hidden', !chosen);
                });
                function undoNewFile() {
                    const input = document.getElementById('pdf-input');
                    input.value = '';
                    input.dispatchEvent(new Event('change'));
                }
            </script>
            <p class="text-xs text-gray-500 mt-1">
                PDF only, up to 50 MB. Shown in the protected view-only reader (no download).
                The text inside the PDF is read automatically, so users can find it by any word in the file.
                @if ($resource->file_path) Leave empty to keep the current file. @endif
            </p>
        </div>

        {{-- Online link --}}
        <div id="source-link" class="{{ $sourceType === 'link' ? '' : 'hidden' }}">
            <label class="block text-sm font-medium mb-1">Link (URL) <span class="text-red-600">*</span></label>
            <input type="url" name="external_url" value="{{ old('external_url', $resource->external_url) }}" placeholder="https://..."
                   class="w-full border rounded px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">
                For resources hosted online (open-access journals, publisher e-books, institutional repositories).
                The link opens on the publisher's site, so download rules are controlled by that site.
                @if ($recommendation && $recommendation->source_link) Pre-filled from the faculty member's recommendation. @endif
            </p>
        </div>
    </div>

    <script>
        document.getElementById('source_type').addEventListener('change', function () {
            document.getElementById('source-file').classList.toggle('hidden', this.value !== 'file');
            document.getElementById('source-link').classList.toggle('hidden', this.value !== 'link');
        });
    </script>

    <div class="flex flex-wrap gap-3">
        <button class="bg-csu-700 text-white px-5 py-2 rounded hover:bg-csu-800">{{ $resource->exists ? 'Save Changes' : 'Add to Repository' }}</button>
        <a href="{{ route('librarian.resources.index') }}" class="px-5 py-2 rounded border hover:bg-gray-50">Cancel</a>
        @if ($resource->exists)
            <button type="submit" form="delete-resource"
                    class="ml-auto px-5 py-2 rounded border border-red-300 text-red-600 hover:bg-red-50">Delete Resource</button>
        @endif
    </div>
</form>

@if ($resource->exists)
    {{-- Separate form for deleting (a form cannot be inside another form) --}}
    <form id="delete-resource" method="POST" action="{{ route('librarian.resources.destroy', $resource) }}"
          data-confirm="“{{ $resource->title }}”, its PDF, and its reading history will be removed. This cannot be undone." data-confirm-title="Delete this resource?" data-confirm-button="Delete" data-confirm-danger
          data-undo="Resource deleted." data-undo-hide="main > *" data-undo-also="main > *" data-undo-go="{{ route('librarian.resources.index') }}">
        @csrf
        @method('DELETE')
    </form>
@endif
@endsection