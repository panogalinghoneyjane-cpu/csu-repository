@extends('layouts.app')
@section('title', 'Student Dashboard')

@section('content')
<h1 class="text-2xl font-bold mb-1">Welcome, {{ auth()->user()->name }}</h1>
<p class="text-sm text-gray-500 mb-6">Find approved learning materials for your subjects.</p>

<div class="mb-8">@include('partials.search-bar')</div>

@if ($recentlyViewed->isNotEmpty())
    <h2 class="text-lg font-semibold mb-3">Continue Reading</h2>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @foreach ($recentlyViewed as $resource)
            @include('partials.resource-card', ['resource' => $resource])
        @endforeach
    </div>
@endif

<h2 class="text-lg font-semibold mb-3">Recommended for You <span class="text-sm font-normal text-gray-500">(popular in your college)</span></h2>
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    @forelse ($popular as $resource)
        @include('partials.resource-card', ['resource' => $resource])
    @empty
        <p class="text-sm text-gray-500">No resources yet.</p>
    @endforelse
</div>

<h2 class="text-lg font-semibold mb-3">Browse by College</h2>
@include('partials.college-grid')
@endsection
