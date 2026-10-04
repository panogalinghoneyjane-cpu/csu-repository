@extends('layouts.app')
@section('title', 'Classifications')

@section('content')
<h1 class="text-2xl font-bold mb-1">Classification Structure</h1>
<p class="text-sm text-gray-500 mb-6">College › Program › Subject. Update this when the university provides curriculum changes. “Target” is the minimum number of up-to-date resources a subject should have (used in adequacy reports).</p>

<div class="grid lg:grid-cols-3 gap-4 mb-8">
    <form method="POST" action="{{ route('librarian.classifications.store', 'college') }}" class="bg-white rounded-lg border p-4 space-y-2 text-sm">
        @csrf
        <h2 class="font-semibold">Add College</h2>
        <input name="code" placeholder="Code (e.g. CCIS)" required class="w-full border rounded px-3 py-2">
        <input name="name" placeholder="Full name" required class="w-full border rounded px-3 py-2">
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
        <h2 class="font-semibold">Add Subject</h2>
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
        <input name="name" placeholder="Subject name" required class="w-full border rounded px-3 py-2">
        <input name="min_resources" type="number" min="1" value="3" placeholder="Target resources" class="w-full border rounded px-3 py-2">
        <button class="bg-csu-700 text-white px-4 py-2 rounded hover:bg-csu-800">Add Subject</button>
    </form>
</div>

<div class="space-y-4">
    @foreach ($colleges as $college)
        <details class="bg-white rounded-lg border" @if ($loop->first) open @endif>
            <summary class="px-5 py-3 cursor-pointer flex items-center justify-between">
                <span class="font-semibold">{{ $college->code }} – {{ $college->name }}</span>
                <form method="POST" action="{{ route('librarian.classifications.destroy', ['college', $college->id]) }}" onsubmit="return confirm('Delete this college?')">
                    @csrf @method('DELETE')<button class="text-xs text-red-600 hover:underline">Delete</button>
                </form>
            </summary>
            <div class="px-5 pb-4 space-y-3">
                @forelse ($college->programs as $program)
                    <div class="border rounded">
                        <div class="flex items-center justify-between bg-gray-50 px-4 py-2">
                            <span class="font-medium">{{ $program->code }} – {{ $program->name }}</span>
                            <form method="POST" action="{{ route('librarian.classifications.destroy', ['program', $program->id]) }}" onsubmit="return confirm('Delete this program?')">
                                @csrf @method('DELETE')<button class="text-xs text-red-600 hover:underline">Delete</button>
                            </form>
                        </div>
                        <table class="w-full text-sm">
                            @forelse ($program->subjects as $subject)
                                <tr class="border-t">
                                    <td class="px-4 py-2">{{ $subject->code }}</td>
                                    <td>{{ $subject->name }}</td>
                                    <td class="text-gray-500">Year {{ $subject->year_level ?? '—' }}</td>
                                    <td class="text-gray-500">{{ $subject->resources_count }} / target {{ $subject->min_resources }}</td>
                                    <td class="pr-4 text-right">
                                        <form method="POST" action="{{ route('librarian.classifications.destroy', ['subject', $subject->id]) }}" onsubmit="return confirm('Delete this subject?')">
                                            @csrf @method('DELETE')<button class="text-xs text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="px-4 py-2 text-gray-500">No subjects yet.</td></tr>
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
