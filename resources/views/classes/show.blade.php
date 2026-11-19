@extends('layouts.app')
@section('title', $class->title)

@section('content')

{{-- Class header --}}
<div class="bg-white rounded-lg border overflow-hidden mt-2 mb-6">
    <div class="bg-csu-700 text-white px-6 py-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold">{{ $class->title }}</h1>
            <p class="text-sm text-csu-100">{{ $class->subject->name }} · {{ $class->subject->program->college->code }} › {{ $class->subject->program->code }}</p>
                        <p class="text-sm text-csu-100 mt-1">{{ $class->faculty->name }}
                @if ($class->semester || $class->school_year) · {{ trim($class->semester.' '.$class->school_year) }} @endif</p>
        </div>
        @if ($isOwner)
            <div class="flex gap-2 text-sm">
                <a href="{{ route('faculty.classes.edit', $class) }}" class="bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded">Edit</a>
                 <form method="POST" action="{{ route('faculty.classes.destroy', $class) }}" data-confirm="Students will lose access to the class page. The resources stay in the repository." data-confirm-title="Delete this class?" data-confirm-button="Delete" data-confirm-danger data-undo="Class deleted." data-undo-hide="main > *" data-undo-also="main > *" data-undo-go="{{ route('classes.index') }}">
                    @csrf @method('DELETE')
                    <button class="bg-white/10 hover:bg-red-600 px-3 py-1.5 rounded">Delete</button>
                </form>
            </div>
        @elseif (auth()->user()->isStudent())
             <form method="POST" action="{{ route('classes.leave', $class) }}" data-confirm="You can join again later using the class link or code." data-confirm-title="Leave this class?" data-confirm-button="Leave Class" data-confirm-danger data-undo="You left the class." data-undo-hide="main > *" data-undo-also="main > *" data-undo-go="{{ route('classes.index') }}">
                @csrf @method('DELETE')
                <button class="text-sm bg-white/10 hover:bg-white/20 px-3 py-1.5 rounded">Leave class</button>
            </form>
        @endif
    </div>
    @if ($class->description)
        <p class="px-6 py-4 text-sm text-gray-700 whitespace-pre-line">{{ $class->description }}</p>
    @endif

    {{-- Teacher: share the class --}}
    @if ($isOwner)
        <div class="px-6 py-4 border-t bg-csu-50 flex flex-wrap items-center gap-4">
            <div class="flex-1 min-w-[16rem]">
                <p class="text-xs font-semibold text-csu-800 mb-1">Share with your students</p>
                <div class="flex gap-2">
                    <input id="share-link" value="{{ $class->shareUrl() }}" readonly class="flex-1 border rounded px-3 py-2 text-sm bg-white" onclick="this.select()">
                    <button type="button" class="bg-csu-700 text-white px-4 rounded text-sm hover:bg-csu-800"
                            onclick="navigator.clipboard.writeText(document.getElementById('share-link').value); this.textContent='Copied ✓'">Copy link</button>
                </div>
            </div>
            <div class="text-center">
                <p class="text-xs font-semibold text-csu-800">Class code</p>
                <p class="text-2xl font-bold tracking-widest text-csu-800">{{ $class->code }}</p>
            </div>
            <div class="text-center">
                <p class="text-xs font-semibold text-csu-800">Students</p>
                <p class="text-2xl font-bold text-csu-800">{{ $students->count() }}</p>
            </div>
            @unless ($class->is_open)
                                <p class="w-full text-xs text-gray-600">This class is closed: new students cannot join (change it in Edit).</p>
            @endunless
        </div>
    @endif
</div>

<div class="grid {{ $isOwner ? 'lg:grid-cols-3' : '' }} gap-6">
    {{-- Resources in this class --}}
    <section class="{{ $isOwner ? 'lg:col-span-2' : '' }}">
        <h2 class="text-lg font-semibold mb-3">Class Resources <span class="text-sm font-normal text-gray-500">({{ $resources->count() }})</span></h2>
        <div class="grid {{ $isOwner ? '' : 'md:grid-cols-2' }} gap-4">
            @forelse ($resources as $resource)
                <div>
                    @include('partials.resource-card', ['resource' => $resource])
                    @if ($resource->pivot->note || $isOwner)
                        <div class="flex items-start justify-between gap-2 mt-1 px-1">
                                                        <p class="text-xs text-gray-600">@if ($resource->pivot->note) Note: <em>{{ $resource->pivot->note }}</em> @endif</p>
                            @if ($isOwner)
                                <form method="POST" action="{{ route('faculty.classes.resources.remove', [$class, $resource]) }}" data-undo="Resource removed from the class." data-undo-hide=".grid > div">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-600 hover:underline whitespace-nowrap">Remove from class</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="bg-white rounded-lg border p-6 text-center text-gray-500 text-sm {{ $isOwner ? '' : 'md:col-span-2' }}">
                    {{ $isOwner ? 'No resources yet. Add some from the panel on the right.' : 'Your teacher has not added resources yet. Check back later.' }}
                </div>
            @endforelse
        </div>

        {{-- Teacher: who saved the class --}}
        @if ($isOwner)
            <h2 class="text-lg font-semibold mt-8 mb-3">Students <span class="text-sm font-normal text-gray-500">({{ $students->count() }})</span></h2>
            <div class="bg-white rounded-lg border overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600"><tr><th class="px-4 py-2">Name</th><th>ID Number</th><th>Saved on</th><th class="pr-4"></th></tr></thead>
                    <tbody>
                    @forelse ($students as $student)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $student->name }}</td>
                            <td>{{ $student->id_number }}</td>
                            <td class="text-gray-500">{{ $student->pivot->created_at->format('M d, Y') }}</td>
                            <td class="pr-4 text-right">
                                <form method="POST" action="{{ route('faculty.classes.students.remove', [$class, $student]) }}"
                                      data-confirm="{{ $student->name }} will no longer see this class. They can join again with the class link or code while the class is open."
                                      data-confirm-title="Remove this student?" data-confirm-button="Remove" data-confirm-danger
                                      data-undo="Student removed from the class." data-undo-hide="tr">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-600 hover:underline">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                                                <tr><td colspan="4" class="px-4 py-4 text-center text-gray-500">No students yet. Share the class link or code.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Teacher: add resources from the repository --}}
    @if ($isOwner)
        <aside>
            <div class="bg-white rounded-lg border p-4 lg:sticky lg:top-4">
                <h2 class="font-semibold mb-1">Add Resources</h2>
                <p class="text-xs text-gray-500 mb-3">
                    {{ $q === '' ? 'Suggested from '.$class->subject->code.'. Search to add from any course.' : 'Search results for “'.$q.'”.' }}
                </p>
                <form method="GET" class="flex gap-2 mb-4">
                    <input name="q" value="{{ $q }}" placeholder="Search the repository…" class="flex-1 border rounded px-3 py-2 text-sm">
                    <button class="bg-gray-800 text-white px-3 rounded text-sm">Search</button>
                </form>
                <div class="space-y-3 max-h-[70vh] overflow-y-auto">
                    @forelse ($candidates as $r)
                        <form method="POST" action="{{ route('faculty.classes.resources.add', $class) }}" class="border rounded p-3">
                            @csrf
                            <input type="hidden" name="resource_id" value="{{ $r->id }}">
                            <p class="text-sm font-medium leading-snug">{{ $r->title }}</p>
                            <p class="text-xs text-gray-500">{{ $r->authors }} · {{ $r->publication_year }} · {{ $r->subject->code }} @if ($r->isLink()) · Link @endif</p>
                            <div class="flex gap-2 mt-2">
                                <input name="note" placeholder="Note (optional)" maxlength="500" class="flex-1 border rounded px-2 py-1 text-xs">
                                <button class="bg-csu-700 text-white px-3 rounded text-xs hover:bg-csu-800">+ Add</button>
                            </div>
                        </form>
                    @empty
                        <p class="text-sm text-gray-500">No more resources found. Try another search.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    @endif
</div>
@endsection
