@extends('layouts.app')
@section('title', 'My Classes')

@section('content')
@php
    $user = auth()->user();
    $archivedClasses = $archivedClasses ?? collect();
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-1">
    <h1 class="text-2xl font-bold">
        {{ $user->isLibrarian() ? 'All Classes' : 'My Classes' }}
    </h1>

    @if ($user->isFaculty())
    <div class="flex items-center gap-2 relative">

        <a href="{{ route('faculty.classes.create') }}"
           class="text-sm bg-csu-700 text-white px-3 py-1.5 rounded hover:bg-csu-800">
            + New Class
        </a>

        <button type="button"
                aria-label="More class options"
                onclick="toggleClassMenu('main-class-menu')"
                class="border bg-white rounded px-2 py-1.5 hover:bg-gray-100">

            <svg width="20" height="20"
                 viewBox="0 0 24 24"
                 fill="currentColor">
                <circle cx="12" cy="5" r="2"/>
                <circle cx="12" cy="12" r="2"/>
                <circle cx="12" cy="19" r="2"/>
            </svg>

        </button>

        <div id="main-class-menu"
             class="class-action-menu hidden absolute right-0 top-10 z-30 w-52 bg-white border rounded-lg shadow-lg">

            <button type="button"
        onclick="openArchivedBox()"
        class="w-full text-left px-4 py-3 text-sm hover:bg-gray-100">
    Archived Classes ({{ $archivedClasses->count() }})
</button>

        </div>

    </div>
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
    <form method="POST"
          action="{{ route('classes.join-code') }}"
          class="bg-white rounded-lg border p-4 mb-6 flex flex-wrap items-end gap-3">

        @csrf

        <label class="block">
            <span class="block text-sm font-medium mb-1">
                Have a class code?
            </span>

            <input name="code"
                   value="{{ old('code') }}"
                   placeholder="e.g. K7Q2MX"
                   maxlength="20"
                   required
                   class="border rounded px-3 py-2 uppercase tracking-widest w-48">
        </label>

        <button class="bg-csu-700 text-white px-4 py-2 rounded hover:bg-csu-800">
            Join Class
        </button>

        <p class="text-xs text-gray-500 w-full">
            Or simply open the class link your teacher sent you.
        </p>
    </form>
@endif

{{-- Active Classes --}}
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">

    @forelse ($classes as $class)

        <div class="bg-white rounded-lg border flex flex-col">

            {{-- Class Header --}}
            <div class="relative flex items-start bg-csu-700 text-white rounded-t-lg">

                <a href="{{ route('classes.show', $class) }}"
                   class="block flex-1 min-w-0 px-4 py-3 hover:bg-csu-800 rounded-t-lg">

                    <p class="font-bold text-lg leading-tight">
                        {{ $class->title }}
                    </p>

                    <p class="text-xs text-csu-100 truncate">
                        {{ $class->subject->name }}
                    </p>
                </a>

                {{-- Faculty Three-dot Menu --}}
                @if ($user->isFaculty())

                    <button type="button"
                            aria-label="Class options"
                            onclick="toggleClassMenu('active-menu-{{ $class->id }}')"
                            class="px-3 py-3 hover:bg-csu-800 rounded-tr-lg">

                        <svg xmlns="http://www.w3.org/2000/svg"
                             width="22"
                             height="22"
                             viewBox="0 0 24 24"
                             fill="currentColor">
                            <circle cx="12" cy="5" r="2"/>
                            <circle cx="12" cy="12" r="2"/>
                            <circle cx="12" cy="19" r="2"/>
                        </svg>

                    </button>

                    <div id="active-menu-{{ $class->id }}"
                         class="class-action-menu hidden absolute right-2 top-12 z-30 w-40 bg-white text-gray-700 border rounded-lg shadow-lg overflow-hidden">

                        <button type="button"
                                onclick="openClassModal('archive-modal-{{ $class->id }}')"
                                class="w-full text-left px-4 py-3 text-sm hover:bg-gray-100">
                            Archive
                        </button>

                        <button type="button"
                                onclick="openClassModal('delete-modal-{{ $class->id }}')"
                                class="w-full text-left px-4 py-3 text-sm text-red-600 border-t hover:bg-red-50">
                            Delete
                        </button>

                    </div>

                @endif

            </div>

            {{-- Class Information --}}
            <div class="p-4 text-sm flex-1 space-y-1">

                @unless ($user->isFaculty())
                    <p class="text-gray-700">
                        {{ $class->faculty->name }}
                    </p>
                @endunless

                <p class="text-gray-500">
                    {{ $class->semester ?? '' }}{{ $class->semester && $class->school_year ? ' · ' : '' }}{{ $class->school_year }}
                </p>

                <p class="text-gray-500">
                    {{ $class->resources_count }}
                    {{ \Illuminate\Support\Str::plural('resource', $class->resources_count) }}

                    @unless ($user->isStudent())
                        · {{ $class->students_count }}
                        {{ \Illuminate\Support\Str::plural('student', $class->students_count) }}
                    @endunless
                </p>

                @if (! $class->is_open)
                    <span class="inline-block text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded">
                        Closed to new students
                    </span>
                @endif

            </div>

            {{-- Class Footer --}}
            <div class="px-4 py-3 border-t flex items-center justify-between gap-2 text-sm">

                <a href="{{ route('classes.show', $class) }}"
                   class="text-csu-700 font-semibold hover:underline">
                    Open class ›
                </a>

                @if ($user->isFaculty())
                    <button type="button"
                            data-share-url="{{ $class->shareUrl() }}"
                            onclick="copyClassLink(this)"
                            class="text-gray-600 hover:text-csu-700">
                        Copy link
                    </button>
                @endif

            </div>

        </div>

        @if ($user->isFaculty())

            {{-- Archive Confirmation Modal --}}
            <div id="archive-modal-{{ $class->id }}"
                 class="class-modal hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="archive-title-{{ $class->id }}">

                <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">

                    <h2 id="archive-title-{{ $class->id }}"
                        class="text-xl font-bold text-gray-800 mb-3">
                        Archive Class?
                    </h2>

                    <p class="text-sm text-gray-600 mb-2">
                        Are you sure you want to archive
                        <strong>{{ $class->title }}</strong>?
                    </p>

                    <p class="text-sm text-gray-500 mb-6">
                        This class will be moved to Archived Classes.
                        Its resources and students will be retained.
                        You can restore it anytime.
                    </p>

                    <div class="flex justify-end gap-3">

                        <button type="button"
                                onclick="closeClassModal('archive-modal-{{ $class->id }}')"
                                class="px-4 py-2 text-sm border rounded-lg hover:bg-gray-100">
                            Cancel
                        </button>

                        <form method="POST"
                              action="{{ route('faculty.classes.archive', $class) }}">
                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                    class="px-4 py-2 text-sm bg-csu-700 text-white rounded-lg hover:bg-csu-800">
                                Archive
                            </button>
                        </form>

                    </div>

                </div>
            </div>

            {{-- Delete Confirmation Modal --}}
            <div id="delete-modal-{{ $class->id }}"
                 class="class-modal hidden fixed inset-0 z-50 items-center justify-center bg-black/50 p-4"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="delete-title-{{ $class->id }}">

                <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">

                    <h2 id="delete-title-{{ $class->id }}"
                        class="text-xl font-bold text-red-600 mb-3">
                        Delete Class?
                    </h2>

                    <p class="text-sm text-gray-600 mb-2">
                        Are you sure you want to permanently delete
                        <strong>{{ $class->title }}</strong>?
                    </p>

                    <p class="text-sm text-gray-500 mb-6">
                        This action cannot be undone.
                        Students will lose access to this class,
                        but the resources will remain in the repository.
                    </p>

                    <div class="flex justify-end gap-3">

                        <button type="button"
                                onclick="closeClassModal('delete-modal-{{ $class->id }}')"
                                class="px-4 py-2 text-sm border rounded-lg hover:bg-gray-100">
                            Cancel
                        </button>

                        <form method="POST"
                              action="{{ route('faculty.classes.destroy', $class) }}">
                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                                Delete
                            </button>
                        </form>

                    </div>

                </div>
            </div>

        @endif

    @empty

        <div class="sm:col-span-2 lg:col-span-3 bg-white rounded-lg border p-8 text-center text-gray-600">

            @if ($user->isFaculty())
                <p class="font-medium">You have no active classes.</p>
                <p class="text-sm mt-1">
                    Create a new class or restore an archived class.
                </p>
            @elseif ($user->isStudent())
                <p class="font-medium">No active saved classes yet.</p>
                <p class="text-sm mt-1">
                    Open the class link from your teacher, or enter the class code above.
                </p>
            @else
                <p class="font-medium">No active classes have been created yet.</p>
            @endif

        </div>

    @endforelse

</div>


@if (! $user->isFaculty() && $archivedClasses->isNotEmpty())
    <button type="button" onclick="openArchivedBox()" class="mt-6 text-csu-700 font-semibold hover:underline">
        Archived Classes ({{ $archivedClasses->count() }})
    </button>
@endif

{{-- Archived Classes Popup --}}
@if ($user->isFaculty() || $archivedClasses->isNotEmpty())

<dialog id="archived-box" aria-labelledby="archived-box-title"
        style="width: min(42rem, 94vw); max-height: 90vh; margin: auto; padding: 0; border: 0; border-radius: 12px;"
        class="bg-white text-gray-800 shadow-xl backdrop:bg-black/50">

    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl">

        {{-- Popup Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b">

            <div>
                <h2 id="archived-box-title"
                    class="text-xl font-bold text-gray-800">
                    Archived Classes
                </h2>

                <p class="text-sm text-gray-500 mt-1">
                    View or restore your archived classes.
                </p>
            </div>

            <button type="button"
                    onclick="closeArchivedBox()"
                    aria-label="Close"
                    class="text-gray-500 hover:text-gray-800 text-2xl">
                &times;
            </button>

        </div>

        {{-- Archived Class List --}}
        <div class="p-6 space-y-3 overflow-y-auto"
             style="max-height: 60vh;">

            @forelse ($archivedClasses as $class)

                <div class="border rounded-lg p-4 flex flex-wrap items-center justify-between gap-4 hover:bg-gray-50">

                    {{-- Clickable Class --}}
                    <a href="{{ route('classes.show', $class) }}"
                       class="flex-1 min-w-0 block">

                        <p class="font-bold text-csu-700 hover:underline">
                            {{ $class->title }}
                        </p>

                        <p class="text-sm text-gray-600">
                            {{ $class->subject->name }}
                        </p>

                        @unless ($user->isFaculty())
                            <p class="text-sm text-gray-500">
                                {{ $class->faculty->name }}
                            </p>
                        @endunless

                        <p class="text-xs text-gray-500 mt-2">
                            {{ $class->semester ?? '' }}
                            {{ $class->semester && $class->school_year ? ' · ' : '' }}
                            {{ $class->school_year }}
                        </p>

                        <p class="text-xs text-gray-500 mt-1">
                            {{ $class->resources_count }}
                            {{ \Illuminate\Support\Str::plural('resource', $class->resources_count) }}

                            @unless ($user->isStudent())
                                · {{ $class->students_count }}
                                {{ \Illuminate\Support\Str::plural('student', $class->students_count) }}
                            @endunless
                        </p>

                        <p class="text-xs font-semibold text-csu-700 mt-2">
                            Open class ›
                        </p>

                    </a>

                    {{-- Unarchive Button --}}
                    @if ($user->isFaculty())

                        <button type="button"
                                onclick="showRestoreConfirmation('{{ $class->id }}')"
                                class="border border-green-700 text-green-700 px-3 py-2 rounded-lg text-sm hover:bg-green-50">
                            Unarchive
                        </button>

                    @endif

                </div>

                @if ($user->isFaculty())

                    {{-- Unarchive Confirmation --}}
                    <div id="restore-confirm-{{ $class->id }}"
                         style="display: none;"
                         class="border rounded-lg bg-gray-50 p-4">

                        <h3 class="font-bold text-gray-800 mb-2">
                            Unarchive Class?
                        </h3>

                        <p class="text-sm text-gray-600 mb-4">
                            Do you want to restore
                            <strong>{{ $class->title }}</strong>
                            to your active classes?
                        </p>

                        <div class="flex justify-end gap-2">

                            <button type="button"
                                    onclick="hideRestoreConfirmation('{{ $class->id }}')"
                                    class="border bg-white px-4 py-2 rounded text-sm">
                                Cancel
                            </button>

                            <form method="POST"
                                  action="{{ route('faculty.classes.restore', $class) }}">

                                @csrf
                                @method('PATCH')

                                <button type="submit"
                                        class="bg-csu-700 text-white px-4 py-2 rounded text-sm hover:bg-csu-800">
                                    Unarchive
                                </button>

                            </form>

                        </div>

                    </div>

                @endif

            @empty

                <div class="text-center py-8 text-gray-500">

                    <p class="font-medium">
                        No archived classes yet.
                    </p>

                    <p class="text-sm mt-1">
                        Archived classes will appear here.
                    </p>

                </div>

            @endforelse

        </div>

        {{-- Popup Footer --}}
        <div class="border-t px-6 py-4 flex justify-end">

            <button type="button"
                    onclick="closeArchivedBox()"
                    class="border rounded-lg px-4 py-2 text-sm hover:bg-gray-100">
                Close
            </button>

        </div>

    </div>

</dialog>

@endif


<script>
    function toggleClassMenu(id) {
        const menu = document.getElementById(id);
        const wasHidden = menu.classList.contains('hidden');

        document.querySelectorAll('.class-action-menu').forEach(function (item) {
            item.classList.add('hidden');
        });

        if (wasHidden) {
            menu.classList.remove('hidden');
        }
    }

    function openClassModal(id) {
        document.querySelectorAll('.class-action-menu').forEach(function (menu) {
            menu.classList.add('hidden');
        });

        const modal = document.getElementById(id);

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        document.body.style.overflow = 'hidden';

        modal.querySelector('button').focus();
    }

    function closeClassModal(id) {
        const modal = document.getElementById(id);

        modal.classList.add('hidden');
        modal.classList.remove('flex');

        document.body.style.overflow = '';
    }

    function openArchivedBox() {
        const box = document.getElementById('archived-box');
        if (!box) return;
        document.querySelectorAll('.class-action-menu').forEach(menu => menu.classList.add('hidden'));
        if (!box.open) box.showModal();
        document.body.style.overflow = 'hidden';
        box.querySelector('button').focus();
    }

    function closeArchivedBox() {
        const box = document.getElementById('archived-box');
        if (!box || !box.open) return;
        box.close();
        document.body.style.overflow = '';
        box.querySelectorAll('[id^="restore-confirm-"]').forEach(item => item.style.display = 'none');
    }

    function showRestoreConfirmation(id) {
        const item = document.getElementById('restore-confirm-' + id);
        if (item) item.style.display = 'block';
    }

    function hideRestoreConfirmation(id) {
        const item = document.getElementById('restore-confirm-' + id);
        if (item) item.style.display = 'none';
    }

    document.getElementById('archived-box')?.addEventListener('cancel', function(event) {
        event.preventDefault();
        closeArchivedBox();
    });

    async function copyClassLink(button) {
        try {
            await navigator.clipboard.writeText(button.dataset.shareUrl);
            button.textContent = 'Link copied ✓';
        } catch (error) {
            window.prompt('Copy this class link:', button.dataset.shareUrl);
        }
    }

    document.addEventListener('click', function (event) {
        document.querySelectorAll('.class-action-menu').forEach(function (menu) {
            if (!menu.parentElement.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });

        if (event.target.id === 'archived-box') {
            const r = event.target.getBoundingClientRect();
            if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) closeArchivedBox();
        }

        if (event.target.classList.contains('class-modal')) {
            closeClassModal(event.target.id);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeArchivedBox();
            document.querySelectorAll('.class-modal').forEach(function (modal) {
                if (!modal.classList.contains('hidden')) {
                    closeClassModal(modal.id);
                }
            });

            document.querySelectorAll('.class-action-menu').forEach(function (menu) {
                menu.classList.add('hidden');
            });
        }
    });
</script>

@endsection
