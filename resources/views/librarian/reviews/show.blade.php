@extends('layouts.app')
@section('title', 'Review Recommendation')

@section('content')
<div class="grid lg:grid-cols-3 gap-6 items-start">
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

        @php
            $revisionNote = $recommendation->histories()->where('action', 'resubmitted')
                ->whereNotNull('remarks')->latest('id')->value('remarks');
        @endphp
        @if ($recommendation->status === 'pending' && $revisionNote)
            <div class="mt-5 rounded-lg border border-orange-200 bg-orange-50 px-4 py-3 text-sm">
                <p class="font-semibold text-orange-800">Faculty's revision comment</p>
                <p class="text-gray-800 whitespace-pre-line mt-1">{{ $revisionNote }}</p>
            </div>
        @endif

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

    {{-- Right side: Decision box, then History below it --}}
    <aside class="space-y-6">
     <div class="bg-white rounded-lg border p-6">
        @if (in_array($recommendation->status, ['pending', 'needs_revision']))
            <h2 class="font-semibold mb-3">Decision</h2>
            <form method="POST" action="{{ route('librarian.reviews.decide', $recommendation) }}" class="space-y-3">
                @csrf
                <label class="flex items-center gap-2"><input type="radio" name="decision" value="approved" required> Approve for purchase</label>
                <label class="flex items-center gap-2"><input type="radio" name="decision" value="needs_revision"> Return for revision</label>
                <label class="flex items-center gap-2"><input type="radio" name="decision" value="rejected"> Reject</label>
                <textarea name="librarian_remarks" rows="4" class="w-full border rounded px-3 py-2 text-sm"
                          placeholder="Remarks for the faculty member (required when returning or rejecting)">{{ old('librarian_remarks') }}</textarea>
                <button class="w-full bg-csu-700 text-white py-2 rounded hover:bg-csu-800">Save Decision</button>
            </form>
        @elseif ($recommendation->status === 'approved')
            <h2 class="font-semibold mb-2">For Purchase</h2>
            <p class="text-sm text-gray-600 mb-3">Approved for purchase. Once the library has bought it, upload it to add it to the repository.</p>
            <a href="{{ route('librarian.resources.create', ['recommendation' => $recommendation->id]) }}" class="block text-center bg-csu-700 text-white py-2 rounded hover:bg-csu-800">Add to the Repository</a>
        @elseif ($recommendation->status === 'fulfilled' && $recommendation->resource)
            <h2 class="font-semibold mb-2">Added to the Repository</h2>
            <p class="text-sm text-gray-600 mb-3">This book was bought and is now in the repository.</p>
            <a href="{{ route('reader.show', $recommendation->resource) }}" class="block text-center border py-2 rounded hover:bg-gray-50">Open Resource</a>
        @else
            <p class="text-sm text-gray-600">This recommendation was rejected.</p>
        @endif
    </div>

    @include('partials.recommendation-history', ['class' => 'bg-white rounded-lg border p-6 max-h-[70vh] overflow-y-auto'])
    </aside>
</div>
@endsection