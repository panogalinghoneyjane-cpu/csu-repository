@extends('layouts.app')
@section('title', 'Access Logs')

@section('content')
@php
    $roleLabels = ['student' => 'Students', 'faculty' => 'Faculty', 'librarian' => 'Librarians'];
@endphp

<a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:underline">‹ Back to Dashboard</a>

<h1 class="text-2xl font-bold mt-2 mb-1">Access Logs{{ $role ? ' – '.$roleLabels[$role] : '' }}</h1>
<p class="text-sm text-gray-500 mb-4">
    Who opened which resource, and when. {{ number_format($logs->total()) }} record{{ $logs->total() === 1 ? '' : 's' }}
    from {{ $f['from']->format('M d, Y') }} to {{ $f['to']->format('M d, Y') }}.
</p>

{{-- Filters --}}
<form class="bg-white rounded-lg border p-4 mb-4 grid sm:grid-cols-2 lg:grid-cols-5 gap-3 text-sm">
    {{-- keep the report filters (college, program, type, academic year) chosen on the Reports page --}}
    @foreach (['college', 'program', 'type', 'academic_year'] as $keep)
        @if (request($keep))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif
    @endforeach

    <select name="role" class="border rounded px-3 py-2">
        <option value="">All roles</option>
        @foreach ($roleLabels as $value => $label)
            <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <input name="q" value="{{ $search }}" placeholder="Search name, ID number, or title" class="border rounded px-3 py-2 lg:col-span-2">
    <input type="date" name="from" value="{{ $f['from']->toDateString() }}" class="border rounded px-3 py-2" title="From">
    <div class="flex gap-2">
        <input type="date" name="to" value="{{ $f['to']->toDateString() }}" class="border rounded px-3 py-2 w-full" title="To">
        <button class="bg-csu-700 text-white px-4 rounded hover:bg-csu-800">Filter</button>
    </div>
</form>

{{-- Log table --}}
<div class="bg-white rounded-lg border overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-600">
            <tr><th class="px-4 py-3">Date & Time</th><th>User</th><th>ID Number</th><th>Role</th><th>Resource Opened</th><th class="pr-4">Course</th></tr>
        </thead>
        <tbody>
        @forelse ($logs as $log)
            <tr class="border-t">
                <td class="px-4 py-2 whitespace-nowrap">{{ $log->accessed_at->format('M d, Y h:i A') }}</td>
                <td>{{ $log->user->name }}</td>
                <td class="text-gray-600">{{ $log->user->id_number }}</td>
                <td class="capitalize">{{ $log->user->role }}</td>
                <td><a href="{{ route('reader.show', $log->resource) }}" class="text-csu-700 hover:underline">{{ $log->resource->title }}</a></td>
                <td class="pr-4 text-gray-600">{{ $log->resource->subject->code }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No access records for these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>

<p class="mt-4 text-xs text-gray-400">Access records are visible to librarians only and are used for repository monitoring.</p>
@endsection