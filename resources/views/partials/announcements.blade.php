{{-- Announcement carousel for the faculty and student dashboards ("expanding cards"):
     the selected announcement is a wide card with everything in it; the others are narrow
     coloured strips beside it. Click a strip, the arrows or the dots to open another one.
     On phones only the selected card shows (swipe or use the arrows). --}}
@php
    // Order: pinned first, then the nearest upcoming event date, then the ones without a date (newest first),
    // and announcements whose event already passed at the end.
    $today = today();
    $announcements = \App\Models\Announcement::visibleTo(auth()->user())->get()
        ->sortBy(function ($a) use ($today) {
            $group = ! $a->event_date ? 1 : ($a->event_date->lt($today) ? 2 : 0);
            $when = $group === 0 ? $a->event_date->timestamp : -$a->created_at->timestamp;
            return [$a->is_pinned ? 0 : 1, $group, $when];
        })
        ->take(8)
        ->values();

@endphp

@if ($announcements->isNotEmpty())
    <section class="ann-carousel bg-white rounded-lg border border-l-4 border-l-orange-500 w-full lg:w-[36rem] shrink-0 self-start">
        <div class="flex items-center gap-2 px-5 pt-4 pb-3">
            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M3 11v2a1 1 0 001 1h2l5 4V6L6 10H4a1 1 0 00-1 1zM15.5 8.5a5 5 0 010 7M18.5 5.5a9 9 0 010 13" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h2 class="text-lg font-semibold">Announcements</h2>
            @if ($announcements->count() > 1)
                <span class="ml-auto text-xs text-gray-500 ann-counter">1 of {{ $announcements->count() }}</span>
            @endif
        </div>

        <div class="ann-track flex gap-2 px-4 h-[30rem]">
            @foreach ($announcements as $i => $a)
                <article class="ann-card {{ $i === 0 ? 'is-active' : '' }}" data-index="{{ $i }}"
                         @if ($i !== 0) role="button" tabindex="0" aria-label="Open announcement: {{ $a->title }}" @endif>
                    {{-- collapsed: the title written sideways on the coloured strip --}}
                    <div class="ann-strip-label">{{ \Illuminate\Support\Str::limit($a->title, 40) }}</div>

                    {{-- expanded: the whole announcement --}}
                    <div class="ann-body">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-gray-900 text-base">{{ $a->title }}</p>
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
                            <button type="button" onclick="openPoster('{{ $a->imageUrl() }}')" title="Click to view full size"
                                    class="mt-3 block max-w-full rounded-lg overflow-hidden border shadow-sm hover:opacity-90 bg-white">
                                <img src="{{ $a->imageUrl() }}" alt="{{ $a->title }}" loading="lazy" class="block max-h-64 w-auto max-w-full">
                            </button>
                        @endif
                        <p class="text-xs text-gray-400 mt-2">Posted {{ $a->created_at->format('M d, Y') }}</p>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($announcements->count() > 1)
            <div class="flex items-center justify-center gap-4 py-3">
                <button type="button" class="ann-prev h-8 w-8 rounded-full hover:bg-gray-100 text-2xl leading-none text-gray-700" aria-label="Previous announcement">&lsaquo;</button>
                <div class="flex items-center gap-2">
                    @foreach ($announcements as $i => $a)
                        <button type="button" class="ann-dot {{ $i === 0 ? 'is-active' : '' }}" data-index="{{ $i }}" aria-label="Announcement {{ $i + 1 }}"></button>
                    @endforeach
                </div>
                <button type="button" class="ann-next h-8 w-8 rounded-full hover:bg-gray-100 text-2xl leading-none text-gray-700" aria-label="Next announcement">&rsaquo;</button>
            </div>
        @else
            <div class="pb-4"></div>
        @endif
    </section>
@endif

@once
    <style>
        /* Expanding-card carousel */
        /* minimal look: plain light-grey strips, and the open announcement without a box or colour */
        .ann-card { position: relative; flex: 0 0 2.5rem; border-radius: 1.25rem; background: #f3f4f6; cursor: pointer;
                    overflow: hidden; transition: flex-basis .45s ease, flex-grow .45s ease, background-color .2s ease; }
        .ann-card:hover:not(.is-active) { background: #e5e7eb; }
        .ann-card.is-active { flex: 1 1 0%; cursor: default; background: transparent; border-radius: 0; }
        .ann-strip-label { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
                           writing-mode: vertical-rl; transform: rotate(180deg); color: #4b5563; font-size: .78rem; font-weight: 500;
                           white-space: nowrap; overflow: hidden; padding: 1rem 0; }
        .ann-card.is-active .ann-strip-label { display: none; }
        .ann-body { display: none; height: 100%; overflow-y: auto; padding: .25rem .5rem .25rem .25rem; }
        .ann-card.is-active .ann-body { display: block; animation: ann-fade .35s ease; }
        @keyframes ann-fade { from { opacity: 0; } to { opacity: 1; } }
        .ann-dot { width: .6rem; height: .6rem; border-radius: 9999px; background: #d1d5db; }
        .ann-dot.is-active { background: #4b5563; }
        /* Show only the active announcement */
.ann-card:not(.is-active) {
    display: none;
}

@media (max-width: 639px) {
    .ann-track {
        height: auto;
        min-height: 16rem;
    }
}
    </style>
    <script>
        document.querySelectorAll('.ann-carousel').forEach(box => {
            const cards = [...box.querySelectorAll('.ann-card')];
            const dots = [...box.querySelectorAll('.ann-dot')];
            const counter = box.querySelector('.ann-counter');
            let current = 0;

            function show(i) {
                current = (i + cards.length) % cards.length;
                cards.forEach((c, n) => {
                    const on = n === current;
                    c.classList.toggle('is-active', on);
                    if (on) { c.removeAttribute('role'); c.removeAttribute('tabindex'); }
                    else { c.setAttribute('role', 'button'); c.setAttribute('tabindex', '0'); }
                });
                dots.forEach((d, n) => d.classList.toggle('is-active', n === current));
                if (counter) counter.textContent = (current + 1) + ' of ' + cards.length;
            }

            cards.forEach((c, n) => {
                c.addEventListener('click', () => { if (n !== current) show(n); });
                c.addEventListener('keydown', e => { if ((e.key === 'Enter' || e.key === ' ') && n !== current) { e.preventDefault(); show(n); } });
            });
            dots.forEach((d, n) => d.addEventListener('click', () => show(n)));
            box.querySelector('.ann-prev')?.addEventListener('click', () => show(current - 1));
            box.querySelector('.ann-next')?.addEventListener('click', () => show(current + 1));

            // swipe left / right on phones
            let startX = null;
            box.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
            box.addEventListener('touchend', e => {
                if (startX === null) return;
                const dx = e.changedTouches[0].clientX - startX;
                if (Math.abs(dx) > 50) show(current + (dx < 0 ? 1 : -1));
                startX = null;
            });
        });
    </script>

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
