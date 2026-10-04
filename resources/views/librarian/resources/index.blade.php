@extends('layouts.app')
@section('title', 'Manage Resources')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-2xl font-bold">Manage Resources</h1>
    <a href="{{ route('librarian.resources.create') }}" class="bg-csu-700 text-white px-4 py-2 rounded hover:bg-csu-800">+ Upload Resource</a>
</div>

<form class="bg-white rounded-lg border p-4 mb-4 grid sm:grid-cols-5 gap-3 text-sm">
    <input name="q" value="{{ request('q') }}" placeholder="Search title, author, keyword..." class="sm:col-span-2 border rounded px-3 py-2">
    <select name="college" class="border rounded px-3 py-2">
        <option value="">All colleges</option>
        @foreach ($colleges as $c)<option value="{{ $c->id }}" @selected(request('college') == $c->id)>{{ $c->code }}</option>@endforeach
    </select>
    <select name="status" class="border rounded px-3 py-2">
        <option value="">Any status</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="archived" @selected(request('status') === 'archived')>Archived</option>
    </select>
    <div class="flex items-center gap-3">
        <label class="flex items-center gap-1"><input type="checkbox" name="outdated" value="1" @checked(request('outdated'))> Outdated only</label>
        <button class="bg-gray-800 text-white px-3 py-2 rounded">Filter</button>
    </div>
</form>

<div class="bg-white rounded-lg border overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-600">
            <tr><th class="px-4 py-3">Title</th><th>Classification</th><th>Year</th><th>Type</th><th>Views</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
        @forelse ($resources as $r)
            <tr class="border-t">
                <td class="px-4 py-3">
                    <a href="{{ route('reader.show', $r) }}" class="font-medium hover:underline">{{ $r->title }}</a>
                    <p class="text-xs text-gray-500">{{ $r->authors }}</p>
                </td>
                <td class="text-xs">{{ $r->subject->program->college->code }} › {{ $r->subject->program->code }} › {{ $r->subject->code }}</td>
                <td class="{{ $r->isOutdated() ? 'text-orange-700 font-semibold' : '' }}">{{ $r->publication_year }}</td>
                <td>{{ $r->resource_type }}</td>
                <td>{{ $r->access_logs_count }}</td>
                <td><span class="text-xs px-2 py-0.5 rounded {{ $r->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">{{ ucfirst($r->status) }}</span></td>
                <td class="pr-4 text-right whitespace-nowrap">
                    <a href="{{ route('librarian.resources.edit', $r) }}" class="text-csu-700 hover:underline">Edit</a>
                    <form method="POST" action="{{ route('librarian.resources.destroy', $r) }}" class="inline" onsubmit="return confirm('Delete this resource permanently?')">
                        @csrf @method('DELETE')
                        <button class="text-red-600 hover:underline ml-2">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">No resources found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $resources->links() }}</div>
@endsection
