{{-- Announcement box for faculty and student dashboards.
     The box is only as wide as its content (a tall poster makes a narrow, tall box). --}}
@php
    $announcements = \App\Models\Announcement::visibleTo(auth()->user())->take(5)->get();
@endphp

@if ($announcements->isNotEmpty())
    <section class="bg-white rounded-lg border border-l-4 border-l-orange-500 w-full lg:w-fit lg:max-w-sm shrink-0 self-start">
        <div class="flex items-center gap-2 px-5 pt-4 pb-2">
            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 11v2a1 1 0 001 1h2l5 4V6L6 10H4a1 1 0 00-1 1zM15.5 8.5a5 5 0 010 7M18.5 5.5a9 9 0 010 13" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h2 class="text-lg font-semibold">Announcements</h2>
        </div>
        <div class="divide-y">
            @foreach ($announcements as $a)
                <div class="px-5 py-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-gray-900">{{ $a->title }}</p>
                        @if ($a->is_pinned)<span class="text-xs px-2 py-0.5 rounded bg-orange-100 text-orange-800">Pinned</span>@endif
                    </div>
                    @if ($a->event_date)
                        <p class="text-sm text-csu-700 font-medium">{{ $a->event_date->format('l, F d, Y') }}
                            @if ($a->event_date->isToday()) <span class="text-orange-600">(today)</span>
                            @elseif ($a->event_date->isFuture()) <span class="text-gray-500 font-normal">({{ (int) today()->diffInDays($a->event_date) }} day{{ today()->diffInDays($a->event_date) == 1 ? '' : 's' }} to go)</span>
                            @endif
                        </p>
                    @endif
                    <p class="text-sm text-gray-700 whitespace-pre-line mt-1">{{ $a->body }}</p>
                    @if ($a->imageUrl())
                        {{-- The frame follows the picture's own shape --}}
                        <button type="button" onclick="openPoster('{{ $a->imageUrl() }}')" title="Click to view full size"
                                class="mt-3 block max-w-full rounded-lg overflow-hidden border shadow-sm hover:opacity-90">
                            <img src="{{ $a->imageUrl() }}" alt="{{ $a->title }}" loading="lazy"
                                 class="block max-h-[26rem] w-auto max-w-full">
                        </button>
                    @endif
                    <p class="text-xs text-gray-400 mt-2">Posted {{ $a->created_at->format('M d, Y') }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endif

@once
    {{-- Full-size poster viewer --}}
    <div id="poster-viewer" class="hidden fixed inset-0 z-[95] bg-black/80 flex items-center justify-center p-4" onclick="closePoster()">
        <img id="poster-viewer-img" src="" alt="Poster" class="max-h-full max-w-full object-contain rounded shadow-2xl">
        <button type="button" class="absolute top-4 right-5 text-white text-4xl leading-none" title="Close">&times;</button>
    </div>
    <script>
        function openPoster(src) {
            document.getElementById('poster-viewer-img').src = src;
            document.getElementById('poster-viewer').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
        function closePoster() {
            document.getElementById('poster-viewer').classList.add('hidden');
            document.body.style.overflow = '';
        }
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closePoster(); });
    </script>
@endonce
