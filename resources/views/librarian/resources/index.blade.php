@extends('layouts.app')
@section('title', 'Manage Resources')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-2xl font-bold">Manage Resources</h1>
        <a href="{{ route('librarian.resources.create') }}" class="self-start shrink-0 text-sm bg-csu-700 text-white px-3 py-1.5 rounded hover:bg-csu-800">+ Upload Resource</a>
</div>

<form class="bg-white rounded-lg border p-4 mb-4 grid sm:grid-cols-5 gap-3 text-sm">
    <input name="q" value="{{ request('q') }}" placeholder="Search title, author, or year..." class="sm:col-span-2 border rounded px-3 py-2">
    <select name="college" class="border rounded px-3 py-2">
        <option value="">All colleges</option>
        @foreach ($colleges as $c)<option value="{{ $c->id }}" @selected(request('college') == $c->id)>{{ $c->code }}</option>@endforeach
    </select>
    <select name="status" class="border rounded px-3 py-2">
        <option value="">All resources</option>
        <option value="archived" @selected(request('status') === 'archived')>Hidden only</option>
    </select>
    <div class="flex items-center gap-3">
        <label class="flex items-center gap-1"><input type="checkbox" name="outdated" value="1" @checked(request('outdated'))> Outdated only</label>
        <button class="bg-gray-800 text-white px-3 py-2 rounded">Filter</button>
    </div>
</form>

<div class="bg-white rounded-lg border overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-600">
            <tr>
                <th class="px-4 py-3">Title</th>
                <th class="px-4 py-3">Classification</th>
                <th class="px-4 py-3">Year</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3 text-center">Views</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($resources as $r)
            <tr class="border-t hover:bg-gray-50">
                <td class="px-4 py-3">
                    <a href="{{ route('reader.show', $r) }}" class="font-medium hover:underline">{{ $r->title }}</a>
                    @if ($r->status === 'archived')
                        <span class="ml-1 text-xs px-2 py-0.5 rounded bg-gray-200 text-gray-700">Hidden</span>
                    @endif
                    <p class="text-xs text-gray-500">{{ $r->authors }}</p>
                </td>
                <td class="px-4 py-3 text-xs whitespace-nowrap">
                    <a href="{{ route('browse.college', $r->subject->program->college) }}" class="text-csu-700 hover:underline" title="{{ $r->subject->program->college->name }}">{{ $r->subject->program->college->code }}</a>
                    ›
                    <a href="{{ route('browse.program', $r->subject->program) }}" class="text-csu-700 hover:underline" title="{{ $r->subject->program->name }}">{{ $r->subject->program->code }}</a>
                    ›
                    <a href="{{ route('browse.subject', $r->subject) }}" class="text-csu-700 hover:underline" title="{{ $r->subject->name }}">{{ $r->subject->code }}</a>
                </td>
                <td class="px-4 py-3 {{ $r->isOutdated() ? 'text-orange-700 font-semibold' : '' }}">{{ $r->publication_year }}</td>
                {{-- Plain text: the title already opens the file --}}
                <td class="px-4 py-3 whitespace-nowrap">{{ $r->resource_type }}</td>
                <td class="px-4 py-3 text-center">{{ $r->access_logs_count }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('librarian.resources.edit', $r) }}" class="text-csu-700 hover:underline">Edit</a>
                    <form method="POST" action="{{ route('librarian.resources.destroy', $r) }}" class="inline" data-confirm="This resource, its PDF, and its reading history will be removed. This cannot be undone." data-confirm-title="Delete this resource?" data-confirm-button="Delete" data-confirm-danger data-undo="Resource deleted." data-undo-hide="tr">
                        @csrf @method('DELETE')
                        <button class="text-red-600 hover:underline ml-3">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No resources found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $resources->links() }}</div>
@endsection
