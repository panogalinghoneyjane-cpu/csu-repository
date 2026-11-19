@extends('layouts.app')
@section('title', 'Announcements')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Announcements</h1>
        <p class="text-sm text-gray-500">Posts here appear on the dashboard of faculty and students (e.g. book fair schedule).</p>
    </div>
    <a href="{{ route('librarian.announcements.create') }}" class="bg-csu-700 text-white px-4 py-2 rounded hover:bg-csu-800">+ New Announcement</a>
</div>

<div class="space-y-3">
    @forelse ($announcements as $a)
        <div class="bg-white rounded-lg border p-5 {{ $a->isExpired() ? 'opacity-60' : '' }}">
            {{-- Text on the left, Edit + Delete always at the top right --}}
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        @if ($a->is_pinned)<span class="text-xs px-2 py-0.5 rounded bg-orange-100 text-orange-800">Pinned</span>@endif
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-700">{{ \App\Models\Announcement::AUDIENCES[$a->audience] }}</span>
                        @if ($a->isExpired())<span class="text-xs px-2 py-0.5 rounded bg-gray-200 text-gray-600">Ended – no longer shown</span>@endif
                    </div>
                    <h2 class="font-semibold text-lg">{{ $a->title }}</h2>
                    @if ($a->event_date)
                        <p class="text-sm text-csu-700 font-medium">Event date: {{ $a->event_date->format('l, F d, Y') }}</p>
                    @endif
                    <p class="text-sm text-gray-700 whitespace-pre-line mt-2">{{ $a->body }}</p>
                    @if ($a->imageUrl())
                        <img src="{{ $a->imageUrl() }}" alt="Poster" class="mt-3 max-h-40 w-auto max-w-full object-contain rounded border bg-gray-50">
                    @endif
                    <p class="text-xs text-gray-400 mt-2">
                        Posted {{ $a->created_at->format('M d, Y h:i A') }}{{ $a->author ? ' by '.$a->author->name : '' }}
                        @if ($a->show_until) · Shown until {{ $a->show_until->format('M d, Y') }} @endif
                    </p>
                </div>
                <div class="shrink-0 flex items-center gap-2 text-sm whitespace-nowrap">
                    <a href="{{ route('librarian.announcements.edit', $a) }}" class="text-csu-700 hover:underline px-1">Edit</a>
                    <form method="POST" action="{{ route('librarian.announcements.destroy', $a) }}"
                          data-confirm="“{{ $a->title }}” will be removed from all dashboards." data-confirm-title="Delete this announcement?"
                          data-confirm-button="Delete" data-confirm-danger
                          data-undo="Announcement deleted." data-undo-hide=".space-y-3 > div">
                        @csrf @method('DELETE')
                        {{-- Trash icon instead of the word "Delete" --}}
                        <button title="Delete" aria-label="Delete announcement"
                                class="p-1.5 rounded text-red-600 hover:bg-red-50 hover:text-red-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.87 12.14A2 2 0 0116.13 21H7.87a2 2 0 01-1.99-1.86L5 7m5 4v6m4-6v6M4 7h16M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-lg border p-6 text-center text-sm text-gray-500">No announcements yet.</div>
    @endforelse
</div>
<div class="mt-4">{{ $announcements->links() }}</div>
@endsection
