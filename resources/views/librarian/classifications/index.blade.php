@extends('layouts.app')
@section('title', 'Classifications')

@section('content')
<h1 class="text-2xl font-bold mb-1">Classification Structure</h1>
<p class="text-sm text-gray-500 mb-6">College › Program › Course.</p>

<div class="grid lg:grid-cols-3 gap-4 mb-8">
    <form method="POST" action="{{ route('librarian.classifications.store', 'college') }}" enctype="multipart/form-data" class="bg-white rounded-lg border p-4 space-y-2 text-sm">
        @csrf
        <h2 class="font-semibold">Add College</h2>
        <input name="code" placeholder="Code (e.g. CCIS)" required class="w-full border rounded px-3 py-2">
        <input name="name" placeholder="Full name" required class="w-full border rounded px-3 py-2">
        <label class="block">
            <span class="block text-xs text-gray-500 mb-1">Logo (optional, PNG/JPG/WEBP, max 4 MB)</span>
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="w-full border rounded px-3 py-1.5 bg-gray-50 text-xs">
        </label>
        <button class="bg-csu-700 text-white px-4 py-2 rounded hover:bg-csu-800">Add College</button>
    </form>
    <form method="POST" action="{{ route('librarian.classifications.store', 'program') }}" class="bg-white rounded-lg border p-4 space-y-2 text-sm">
        @csrf
        <h2 class="font-semibold">Add Program</h2>
        <select name="college_id" required class="w-full border rounded px-3 py-2">
            @foreach ($colleges as $c)<option value="{{ $c->id }}">{{ $c->code }}</option>@endforeach
        </select>
        <input name="code" placeholder="Code (e.g. BSIT)" required class="w-full border rounded px-3 py-2">
        <input name="name" placeholder="Full name" required class="w-full border rounded px-3 py-2">
        <button class="bg-csu-700 text-white px-4 py-2 rounded hover:bg-csu-800">Add Program</button>
    </form>
    <form method="POST" action="{{ route('librarian.classifications.store', 'subject') }}" class="bg-white rounded-lg border p-4 space-y-2 text-sm">
        @csrf
        <h2 class="font-semibold">Add Course</h2>
        <select name="program_id" required class="w-full border rounded px-3 py-2">
            @foreach ($colleges as $c)
                <optgroup label="{{ $c->code }}">
                    @foreach ($c->programs as $p)<option value="{{ $p->id }}">{{ $p->code }}</option>@endforeach
                </optgroup>
            @endforeach
        </select>
        <div class="grid grid-cols-2 gap-2">
            <input name="code" placeholder="Code (e.g. IT 213)" required class="border rounded px-3 py-2">
            <input name="year_level" type="number" min="1" max="5" placeholder="Year level" class="border rounded px-3 py-2">
        </div>
        <input name="name" placeholder="Course name" required class="w-full border rounded px-3 py-2">
        <button class="bg-csu-700 text-white px-4 py-2 rounded hover:bg-csu-800">Add Course</button>
    </form>
</div>

<div class="space-y-4">
    @foreach ($colleges as $college)
        <details class="bg-white rounded-lg border" @if ($loop->first) open @endif>
            <summary class="px-5 py-3 cursor-pointer flex items-center justify-between gap-3">
                <span class="flex items-center gap-3">
                    @if ($logo = \App\Support\CollegeLogo::url($college->code))
                        <img src="{{ $logo }}" alt="{{ $college->code }} logo" class="h-10 w-10 object-contain rounded border bg-white">
                    @else
                        <span class="h-10 w-10 flex items-center justify-center rounded border border-dashed text-[10px] text-gray-400">No logo</span>
                    @endif
                    <span class="font-semibold">{{ $college->code }} – {{ $college->name }}</span>
                </span>
                <span class="flex items-center gap-4">
                    {{-- Choosing a file uploads it right away --}}
                    <form method="POST" action="{{ route('librarian.classifications.logo', $college) }}" enctype="multipart/form-data">
                        @csrf
                        <label class="text-xs text-csu-700 hover:underline cursor-pointer" onclick="event.stopPropagation()">
                            {{ $logo ? 'Change logo' : 'Upload logo' }}
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="hidden" onchange="this.form.submit()">
                        </label>
                    </form>
                    <form method="POST" action="{{ route('librarian.classifications.destroy', ['college', $college->id]) }}" data-confirm="“{{ $college->code }}” and EVERYTHING inside it will be deleted: {{ $college->programs->count() }} program(s), {{ $college->programs->sum(fn ($p) => $p->subjects->count()) }} course(s) and {{ $college->programs->sum(fn ($p) => $p->subjects->sum('resources_count')) }} resource(s) with their PDF files. This cannot be undone." data-confirm-title="Delete this college?" data-confirm-button="Delete" data-confirm-danger data-undo="College deleted." data-undo-hide="details">
                        @csrf @method('DELETE')<button class="text-xs text-red-600 hover:underline">Delete</button>
                    </form>
                </span>
            </summary>
            <div class="px-5 pb-4 space-y-3">
                @forelse ($college->programs as $program)
                    <div class="border rounded">
                        <div class="flex items-center justify-between bg-gray-50 px-4 py-2">
                            <span class="font-medium">{{ $program->code }} – {{ $program->name }}</span>
                            <form method="POST" action="{{ route('librarian.classifications.destroy', ['program', $program->id]) }}" data-confirm="“{{ $program->code }}” and EVERYTHING inside it will be deleted: {{ $program->subjects->count() }} course(s) and {{ $program->subjects->sum('resources_count') }} resource(s) with their PDF files. This cannot be undone." data-confirm-title="Delete this program?" data-confirm-button="Delete" data-confirm-danger data-undo="Program deleted." data-undo-hide=".border.rounded">
                            </form>
                        </div>
                                               <table class="w-full text-sm table-fixed">
                            <colgroup>
                                <col class="w-28">
                                <col>
                                <col class="w-24">
                                <col class="w-36">
                                <col class="w-24">
                            </colgroup>
                            @forelse ($program->subjects as $subject)
                                <tr class="border-t">
                                    <td class="px-4 py-2 font-medium">{{ $subject->code }}</td>
                                    <td class="px-4 py-2 truncate" title="{{ $subject->name }}">{{ $subject->name }}</td>
                                    <td class="px-4 py-2 text-gray-500">Year {{ $subject->year_level ?? '—' }}</td>
                                    <td class="px-4 py-2 text-gray-500">{{ $subject->resources_count }} / minimum {{ \App\Models\Subject::MIN_RESOURCES }}</td>
                                    <td class="px-4 py-2 text-right">
                                        <form method="POST" action="{{ route('librarian.classifications.destroy', ['subject', $subject->id]) }}" data-confirm="“{{ $subject->code }}” and its {{ $subject->resources_count }} resource(s) with their PDF files will be deleted. This cannot be undone." data-confirm-title="Delete this course?" data-confirm-button="Delete" data-confirm-danger data-undo="Course deleted." data-undo-hide="tr">
                                            @csrf @method('DELETE')<button class="text-xs text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-2 text-gray-500">No courses yet.</td></tr>
                            @endforelse
                        </table>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No programs yet.</p>
                @endforelse
            </div>
        </details>
    @endforeach
</div>
@endsection
