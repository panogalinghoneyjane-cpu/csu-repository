@extends('layouts.app')
@section('title', 'Browse Collection')

@section('content')
<h1 class="text-2xl font-bold mb-4">Browse by College</h1>
<div class="mb-6">@include('partials.search-bar')</div>
@include('partials.college-grid')
@endsection
