{{-- Usage: @include('partials.resource-card', ['resource' => $r, 'reason' => 'optional text', 'snippet' => 'optional', 'term' => 'search words']) --}}
{{-- h-full: cards in the same row are the same height. min-w-0 + break-words: a long title wraps instead of pushing the type label out of the card. --}}
<a href="{{ route('reader.show', $resource) }}" class="flex flex-col h-full min-w-0 bg-white rounded-lg border p-4 hover:border-csu-600 hover:shadow transition">
    <div class="flex items-start justify-between gap-2 min-w-0">
    @php
        // Search words are marked in yellow on the card (title, type, authors, year, snippet)
        $hl = fn ($text) => \App\Models\Resource::highlight($text, $term ?? null);
    @endphp
        <p class="min-w-0 flex-1 font-semibold text-gray-900 leading-snug [overflow-wrap:anywhere]">{{ $hl($resource->title) }}</p>
        <span class="shrink-0 whitespace-nowrap text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded">{{ $hl($resource->resource_type) }}</span>
    </div>
    <p class="text-sm text-gray-600 mt-1">{{ $hl($resource->authors) }} · {{ $hl($resource->publication_year) }}</p>
    @if ($resource->subject)
        {{-- Course is not highlighted here: matching courses already have their own box at the top --}}
        <p class="text-xs text-gray-500 mt-1">{{ $resource->subject->label }}</p>
    @endif
    @if (! empty($snippet))
        @php
            $labels = ['file' => 'Found inside the file:', 'description' => 'Found in the description:', 'link' => 'Found in the link:'];
        @endphp
        <p class="text-xs text-gray-600 mt-2 border-l-2 border-csu-600 pl-2 break-words">
            <span class="font-semibold text-csu-700">{{ $labels[$snippet['where']] }}</span> {{ $hl($snippet['text']) }}
        </p>
    @endif
    <div class="flex flex-wrap items-center gap-2 mt-2">
        @if ($resource->status === 'archived')
            {{-- Only the librarian ever sees hidden resources --}}
            <span class="text-xs bg-gray-200 text-gray-700 px-2 py-0.5 rounded" title="Faculty and students cannot see this resource">Hidden</span>
        @endif
        @if ($resource->has_physical_copy)
            <span class="text-xs bg-csu-50 text-csu-800 border border-csu-100 px-2 py-0.5 rounded"
                  title="{{ trim(($resource->call_number ? 'Call No. '.$resource->call_number : '').($resource->shelf_location ? ' · '.$resource->shelf_location : '')) }}">Physical copy in the library</span>
        @endif
        {{-- Format: every resource shows how it can be read (the Type at the top right is what kind of material it is) --}}
        @if ($resource->isLink())
            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">🔗 Online link</span>
        @elseif ($resource->file_path)
            <span class="text-xs bg-red-50 text-red-800 border border-red-100 px-2 py-0.5 rounded">📄 PDF</span>
        @endif
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
