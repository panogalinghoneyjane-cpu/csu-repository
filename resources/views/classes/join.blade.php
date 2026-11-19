@extends('layouts.app')
@section('title', 'Join '.$class->title)

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg border overflow-hidden">
        <div class="bg-csu-700 text-white px-6 py-5">
            <p class="text-xs uppercase tracking-wide text-csu-100">You're invited to a class</p>
            <h1 class="text-2xl font-bold">{{ $class->title }}</h1>
            <p class="text-sm text-csu-100">{{ $class->subject->name }}</p>
        </div>
        <div class="p-6 space-y-3 text-sm">
                <p>Teacher: <strong>{{ $class->faculty->name }}</strong></p>
            <p>{{ $class->subject->program->college->code }} › {{ $class->subject->program->code }}
                @if ($class->semester || $class->school_year) · {{ trim($class->semester.' '.$class->school_year) }} @endif</p>
            @if ($class->description)<p class="text-gray-700 whitespace-pre-line">{{ $class->description }}</p>@endif
            <p>{{ $class->resources_count }} {{ \Illuminate\Support\Str::plural('resource', $class->resources_count) }} recommended by your teacher</p>

            @if ($preview->isNotEmpty())
                <ul class="list-disc ml-5 text-gray-600">
                    @foreach ($preview as $r)<li>{{ $r->title }}</li>@endforeach
                    @if ($class->resources_count > $preview->count())<li class="list-none -ml-5 text-gray-400">and more…</li>@endif
                </ul>
            @endif

            <div class="pt-3">
                @if (auth()->user()->isStudent())
                    @if ($class->is_open)
                        <form method="POST" action="{{ route('classes.save', $class->code) }}">
                            @csrf
                            <button class="w-full bg-csu-700 text-white py-3 rounded font-semibold hover:bg-csu-800">Save to My Classes</button>
                        </form>
                        <p class="text-xs text-gray-500 mt-2 text-center">You'll find it under <strong>My Classes</strong> and can open its resources anytime.</p>
                    @else
                        <p class="rounded bg-gray-100 px-4 py-3 text-gray-700">This class is closed to new students. Please ask your teacher.</p>
                    @endif
                @else
                    <p class="rounded bg-yellow-50 border border-yellow-200 px-4 py-3 text-yellow-800">Only students can save classes to My Classes.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
