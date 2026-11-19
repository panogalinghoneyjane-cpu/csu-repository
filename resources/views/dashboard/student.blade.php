@extends('layouts.app')
@section('title', 'Student Dashboard')

@section('content')
<h1 class="text-2xl font-bold mb-1">Welcome, {{ auth()->user()->name }}</h1>
<p class="text-sm text-gray-500 mb-6">Find approved learning materials for your courses.</p>

{{-- ===== Announcements (left, as wide as its poster) + Recommended for You (right, fills the rest) ===== --}}
<div class="flex flex-col lg:flex-row gap-6 mb-8">
    @include('partials.announcements')

    {{-- White box like Announcements (without its orange line); every card in a row is the same height --}}
    <section class="flex-1 min-w-0 self-start bg-white rounded-lg border">
        <div class="px-5 pt-4 pb-2">
            <h2 class="text-lg font-semibold">Recommended for You</h2>
        </div>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4 px-5 pb-5 pt-2 [&>a]:h-full">
            @forelse ($popular as $resource)
                @include('partials.resource-card', ['resource' => $resource])
            @empty
                <p class="text-sm text-gray-500">No resources yet.</p>
            @endforelse
        </div>
    </section>
</div>

@if ($recentlyViewed->isNotEmpty())
    <h2 class="text-lg font-semibold mb-3">Continue Reading</h2>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @foreach ($recentlyViewed as $resource)
            @include('partials.resource-card', ['resource' => $resource])
        @endforeach
    </div>
@endif
@endsection
