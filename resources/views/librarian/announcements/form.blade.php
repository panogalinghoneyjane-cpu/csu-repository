@extends('layouts.app')
@section('title', $announcement->exists ? 'Edit Announcement' : 'New Announcement')

@section('content')
<h1 class="text-2xl font-bold mb-1">{{ $announcement->exists ? 'Edit Announcement' : 'New Announcement' }}</h1>
<p class="text-sm text-gray-500 mb-6">This will show on the dashboard of the faculty and/or students you choose.</p>

<form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg border p-6 space-y-5 max-w-3xl"
      action="{{ $announcement->exists ? route('librarian.announcements.update', $announcement) : route('librarian.announcements.store') }}">
    @csrf
    @if ($announcement->exists) @method('PUT') @endif

    <div>
        <label class="block text-sm font-medium mb-1">Title <span class="text-red-600">*</span></label>
        <input name="title" value="{{ old('title', $announcement->title) }}" required maxlength="150"
               placeholder="e.g. Annual Book Fair – December 5, 2026" class="w-full border rounded px-3 py-2">
    </div>

    <div>
        <label class="block text-sm font-medium mb-1">Message <span class="text-red-600">*</span></label>
        <textarea name="body" rows="6" required maxlength="3000" class="w-full border rounded px-3 py-2"
                  placeholder="e.g. Book suppliers will be at the University Library. Faculty, please prepare and submit your book recommendations before the book fair so the library can purchase them.">{{ old('body', $announcement->body) }}</textarea>
    </div>

    {{-- Poster / picture (any shape) --}}
    <div>
        <label class="block text-sm font-medium mb-1">Poster / Picture <span class="text-gray-400 font-normal">(optional)</span></label>

        @if ($announcement->imageUrl())
            <input type="hidden" name="remove_image" id="remove-image" value="0">
            <div id="current-image" class="relative inline-block mb-3">
                <img src="{{ $announcement->imageUrl() }}" alt="Current poster" class="block max-h-56 w-auto max-w-full rounded border">
                <button type="button" onclick="removeCurrentImage()" title="Remove this picture"
                        class="absolute top-2 right-2 w-8 h-8 rounded-full bg-red-600 text-white flex items-center justify-center shadow hover:bg-red-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 002 2h8a2 2 0 002-2l1-12M9 7V4h6v3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <p id="removed-note" class="hidden text-sm text-red-600 mb-3">The picture will be removed when you click Save Changes.
                <button type="button" onclick="undoRemoveImage()" class="underline text-csu-700 ml-1">Undo</button></p>
        @endif
        <input type="file" name="image" id="image-input" accept="image/jpeg,image/png,image/webp,image/gif"
               class="block w-full text-sm border rounded px-3 py-2 file:mr-3 file:px-3 file:py-1 file:rounded file:border-0 file:bg-csu-700 file:text-white">
        <p class="text-xs text-gray-500 mt-1">JPG, PNG, WEBP or GIF, up to 5 MB. Any shape (tall poster, square or wide banner) fits automatically.
            {{ $announcement->imageUrl() ? 'Choosing a new picture replaces the current one.' : '' }}</p>
        <img id="image-preview" class="hidden mt-3 max-h-56 w-auto max-w-full object-contain rounded border bg-gray-50" alt="Preview">
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium mb-1">Show to <span class="text-red-600">*</span></label>
            <select name="audience" class="w-full border rounded px-3 py-2">
                @foreach (\App\Models\Announcement::AUDIENCES as $key => $label)
                    <option value="{{ $key }}" @selected(old('audience', $announcement->audience) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Event date</label>
            <input type="date" name="event_date" value="{{ old('event_date', $announcement->event_date?->format('Y-m-d')) }}" class="w-full border rounded px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">Optional, e.g. the book fair day.</p>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Show until</label>
            <input type="date" name="show_until" value="{{ old('show_until', $announcement->show_until?->format('Y-m-d')) }}" class="w-full border rounded px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">Leave blank to always show.</p>
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $announcement->is_pinned))>
        Pin to the top
    </label>

    <div class="flex gap-3">
        <button class="bg-csu-700 text-white px-5 py-2 rounded hover:bg-csu-800">{{ $announcement->exists ? 'Save Changes' : 'Post Announcement' }}</button>
        <a href="{{ route('librarian.announcements.index') }}" class="px-5 py-2 rounded border hover:bg-gray-50">Cancel</a>
    </div>
</form>

<script>
    // Show a preview of the chosen picture (it replaces the current one)
    document.getElementById('image-input').addEventListener('change', e => {
        const preview = document.getElementById('image-preview');
        const file = e.target.files[0];
        if (!file) { preview.classList.add('hidden'); return; }
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
        document.getElementById('current-image')?.classList.add('hidden');
        document.getElementById('removed-note')?.classList.add('hidden');
    });

    // Trash button on the current picture
    function removeCurrentImage() {
        document.getElementById('remove-image').value = '1';
        document.getElementById('current-image').classList.add('hidden');
        document.getElementById('removed-note').classList.remove('hidden');
    }

    function undoRemoveImage() {
        document.getElementById('remove-image').value = '0';
        document.getElementById('current-image').classList.remove('hidden');
        document.getElementById('removed-note').classList.add('hidden');
    }
</script>
@endsection
