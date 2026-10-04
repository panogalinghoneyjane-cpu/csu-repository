{{-- Usage: @include('partials.resource-card', ['resource' => $r, 'reason' => 'optional text']) --}}
<a href="{{ route('reader.show', $resource) }}" class="block bg-white rounded-lg border p-4 hover:border-csu-600 hover:shadow transition">
    <div class="flex items-start justify-between gap-2">
        <p class="font-semibold text-gray-900 leading-snug">{{ $resource->title }}</p>
        <span class="shrink-0 text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded">{{ $resource->resource_type }}</span>
    </div>
    <p class="text-sm text-gray-600 mt-1">{{ $resource->authors }} · {{ $resource->publication_year }}</p>
    @if ($resource->subject)
        <p class="text-xs text-gray-500 mt-1">{{ $resource->subject->label }}</p>
    @endif
    <div class="flex flex-wrap items-center gap-2 mt-2">
        @if ($resource->isOutdated())
            <span class="text-xs bg-orange-100 text-orange-800 px-2 py-0.5 rounded">Older than {{ \App\Models\Resource::OUTDATED_AFTER_YEARS }} years</span>
        @endif
        @isset($resource->access_logs_count)
            <span class="text-xs text-gray-500">{{ $resource->access_logs_count }} views</span>
        @endisset
        @if (! empty($reason))
            <span class="text-xs text-csu-700">★ {{ $reason }}</span>
        @endif
    </div>
</a>
