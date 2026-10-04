@extends('layouts.app')
@section('title', 'Review Recommendation')

@section('content')
<a href="{{ route('librarian.reviews.index') }}" class="text-sm text-gray-500 hover:underline">‹ Back to recommendations</a>

<div class="grid lg:grid-cols-3 gap-6 mt-3">
    <section class="lg:col-span-2 bg-white rounded-lg border p-6">
        <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
            <h1 class="text-xl font-bold">{{ $recommendation->title }}</h1>
            <span class="text-xs px-2 py-1 rounded {{ $recommendation->statusColor() }}">{{ $recommendation->statusLabel() }}</span>
        </div>
        <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div><dt class="text-gray-500">Author(s)</dt><dd>{{ $recommendation->authors }}</dd></div>
            <div><dt class="text-gray-500">Publisher / Edition</dt><dd>{{ $recommendation->publisher ?? '—' }} {{ $recommendation->edition ? '· '.$recommendation->edition : '' }}</dd></div>
            <div><dt class="text-gray-500">Publication Year</dt><dd>{{ $recommendation->publication_year ?? '—' }}
                @if ($recommendation->publication_year && $recommendation->publication_year < now()->year - \App\Models\Resource::OUTDATED_AFTER_YEARS)
                    <span class="text-xs text-orange-700">(older than {{ \App\Models\Resource::OUTDATED_AFTER_YEARS }} years)</span>
                @endif
            </dd></div>
            <div><dt class="text-gray-500">Type</dt><dd>{{ $recommendation->resource_type }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-gray-500">Classification</dt>
                <dd>{{ $recommendation->subject->program->college->code }} › {{ $recommendation->subject->program->code }} › {{ $recommendation->subject->label }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-gray-500">Source / Link</dt>
                <dd>@if ($recommendation->source_link)<a href="{{ $recommendation->source_link }}" target="_blank" rel="noopener" class="text-csu-700 underline break-all">{{ $recommendation->source_link }}</a>@else — @endif</dd></div>
            <div class="sm:col-span-2"><dt class="text-gray-500">Reason</dt><dd class="whitespace-pre-line">{{ $recommendation->reason }}</dd></div>
            <div><dt class="text-gray-500">Recommended by</dt><dd>{{ $recommendation->faculty->name }} ({{ $recommendation->faculty->id_number }})</dd></div>
            <div><dt class="text-gray-500">Submitted</dt><dd>{{ $recommendation->created_at->format('M d, Y h:i A') }}</dd></div>
            @if ($recommendation->reviewer)
                <div class="sm:col-span-2"><dt class="text-gray-500">Last reviewed</dt>
                    <dd>{{ $recommendation->reviewer->name }} · {{ $recommendation->reviewed_at->format('M d, Y') }} — “{{ $recommendation->librarian_remarks ?? 'no remarks' }}”</dd></div>
            @endif
        </dl>

        @if ($similar->isNotEmpty())
            <div class="mt-6 rounded border border-yellow-200 bg-yellow-50 p-4 text-sm">
                <p class="font-semibold text-yellow-800 mb-2">Possibly already in the repository:</p>
                <ul class="list-disc ml-5 text-yellow-900">
                    @foreach ($similar as $s)
                        <li><a href="{{ route('reader.show', $s) }}" class="underline">{{ $s->title }}</a> ({{ $s->publication_year }}, {{ $s->subject->code }})</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>

    <aside class="bg-white rounded-lg border p-6 h-fit">
        @if (in_array($recommendation->status, ['pending', 'needs_revision']))
            <h2 class="font-semibold mb-3">Decision</h2>
            <form method="POST" action="{{ route('librarian.reviews.decide', $recommendation) }}" class="space-y-3">
                @csrf
                <label class="flex items-center gap-2"><input type="radio" name="decision" value="approved" required> Approve (then upload)</label>
                <label class="flex items-center gap-2"><input type="radio" name="decision" value="needs_revision"> Return for revision</label>
                <label class="flex items-center gap-2"><input type="radio" name="decision" value="rejected"> Reject</label>
                <textarea name="librarian_remarks" rows="4" class="w-full border rounded px-3 py-2 text-sm"
                          placeholder="Remarks for the faculty member (required when returning or rejecting)">{{ old('librarian_remarks') }}</textarea>
                <button class="w-full bg-csu-700 text-white py-2 rounded hover:bg-csu-800">Save Decision</button>
            </form>
        @elseif ($recommendation->status === 'approved')
            <p class="text-sm mb-3">This recommendation is approved. Upload the acquired file to add it to the repository.</p>
            <a href="{{ route('librarian.resources.create', ['recommendation' => $recommendation->id]) }}" class="block text-center bg-blue-600 text-white py-2 rounded hover:bg-blue-700">Upload Resource</a>
        @elseif ($recommendation->status === 'fulfilled' && $recommendation->resource)
            <p class="text-sm mb-3">Added to the repository.</p>
            <a href="{{ route('reader.show', $recommendation->resource) }}" class="block text-center border py-2 rounded hover:bg-gray-50">Open Resource</a>
        @else
            <p class="text-sm text-gray-600">This recommendation was rejected.</p>
        @endif
    </aside>
</div>
@endsection
