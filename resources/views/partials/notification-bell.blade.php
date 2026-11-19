{{-- Notification bell (faculty and students): new announcements show an orange badge.
     Click the bell -> list beside it. Click an announcement -> full announcement in the center. --}}
@php
    $bellUser = auth()->user();
    $showBell = $bellUser && ($bellUser->isFaculty() || $bellUser->isStudent() || $bellUser->isLibrarian());

    if ($showBell) {
        // The librarian sees every current announcement; faculty/students only the ones meant for them
        $bellItems = $bellUser->isLibrarian()
            ? \App\Models\Announcement::with('author')->current()->orderByDesc('is_pinned')->latest()->take(10)->get()
            : \App\Models\Announcement::with('author')->visibleTo($bellUser)->take(10)->get();

        // Announcements this user already opened
        $readIds = \Illuminate\Support\Facades\DB::table('announcement_reads')
            ->where('user_id', $bellUser->id)->whereIn('announcement_id', $bellItems->pluck('id'))
            ->pluck('announcement_id')->all();
        $isNew = fn ($a) => ! in_array($a->id, $readIds);
        $unread = $bellItems->filter($isNew)->count();
    }
@endphp

@if ($showBell)
<div class="relative" id="bell-menu">
    <button type="button" onclick="toggleBell()" title="Notifications"
            class="relative w-8 h-8 md:w-10 md:h-10 shrink-0 rounded-full bg-white/90 text-csu-800 flex items-center justify-center hover:bg-white shadow">
        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.7 21a2 2 0 01-3.4 0" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span id="bell-badge"
              class="{{ $unread ? '' : 'hidden' }} absolute -top-1 -right-1 min-w-[1.25rem] h-5 px-1 rounded-full bg-orange-500 text-white text-[11px] font-bold flex items-center justify-center ring-2 ring-white">{{ $unread > 9 ? '9+' : $unread }}</span>
    </button>

    {{-- List beside the bell --}}
    <div id="bell-dropdown" class="hidden absolute right-0 mt-2 w-[calc(100vw-1.5rem)] max-w-sm sm:w-96 bg-white text-gray-800 rounded-lg shadow-lg border border-csu-700 z-50">
        <div class="flex items-center justify-between px-4 py-3 border-b">
            <p class="font-semibold">Announcements</p>
            <div class="flex items-center gap-3 text-xs">
                <span id="bell-new-count" class="text-orange-600 {{ $unread ? '' : 'hidden' }}">{{ $unread }} new</span>
                <button type="button" id="bell-read-all" onclick="markAllRead()"
                        class="text-csu-700 font-medium hover:underline {{ $unread ? '' : 'hidden' }}">Mark all as read</button>
            </div>
        </div>
        <div class="max-h-96 overflow-y-auto divide-y">
            @forelse ($bellItems as $a)
                <button type="button" onclick="openAnnouncement('ann-{{ $a->id }}', {{ $a->id }})"
                        data-ann="{{ $a->id }}" data-new="{{ $isNew($a) ? 1 : 0 }}"
                        class="bell-item w-full text-left flex gap-3 px-4 py-3 hover:bg-csu-50 {{ $isNew($a) ? 'bg-orange-50' : '' }}">
                    <span class="bell-dot mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $isNew($a) ? 'bg-orange-500' : 'bg-transparent' }}"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-medium text-sm leading-snug">{{ $a->title }}</span>
                        @if ($a->event_date)
                            <span class="block text-xs text-csu-700">{{ $a->event_date->format('M d, Y') }}
                                @if ($a->event_date->isToday()) · today
                                @elseif ($a->event_date->isFuture()) · {{ (int) today()->diffInDays($a->event_date) }} day{{ today()->diffInDays($a->event_date) == 1 ? '' : 's' }} to go
                                @endif
                            </span>
                        @endif
                        <span class="block text-xs text-gray-500 truncate">{{ \Illuminate\Support\Str::limit($a->body, 70) }}</span>
                        <span class="block text-[11px] text-gray-400 mt-0.5">{{ $a->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-500">No announcements yet.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Full announcement in the center (one per announcement) --}}
@foreach ($bellItems as $a)
    <div id="ann-{{ $a->id }}" class="ann-modal hidden fixed inset-0 z-[96] bg-black/50 flex items-center justify-center p-4 text-gray-800"
         onclick="if (event.target === this) closeAnnouncement(this.id)">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col border-t-4 border-orange-500">
            <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-3 border-b">
                <div>
                    <p class="text-xs text-orange-600 uppercase tracking-wide font-semibold">Announcement</p>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $a->title }}</h2>
                    <p class="text-xs text-gray-400">Posted {{ $a->created_at->format('M d, Y h:i A') }}{{ $a->author ? ' by '.$a->author->name : '' }}</p>
                </div>
                <button type="button" onclick="closeAnnouncement('ann-{{ $a->id }}')" class="text-2xl leading-none text-gray-400 hover:text-gray-700" title="Close">&times;</button>
            </div>

            <div class="px-6 py-4 overflow-y-auto space-y-4">
                @if ($a->event_date)
                    {{-- Upcoming event --}}
                    <div class="rounded-lg bg-csu-50 border border-csu-100 px-4 py-3 flex items-center gap-3">
                        <div class="text-center bg-white rounded-md border px-3 py-1 shrink-0">
                            <p class="text-[11px] uppercase text-orange-600 font-semibold">{{ $a->event_date->format('M') }}</p>
                            <p class="text-2xl font-bold text-csu-800 leading-none">{{ $a->event_date->format('d') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-csu-800">{{ $a->event_date->format('l, F d, Y') }}</p>
                            <p class="text-xs text-gray-600">
                                @if ($a->event_date->isToday()) Happening today
                                @elseif ($a->event_date->isFuture()) {{ (int) today()->diffInDays($a->event_date) }} day{{ today()->diffInDays($a->event_date) == 1 ? '' : 's' }} to go
                                @else Event has passed
                                @endif
                            </p>
                        </div>
                    </div>
                @endif

                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $a->body }}</p>

                @if ($a->imageUrl())
                    <a href="{{ $a->imageUrl() }}" target="_blank" title="Open full size" class="block w-fit max-w-full mx-auto rounded-lg overflow-hidden border">
                        <img src="{{ $a->imageUrl() }}" alt="{{ $a->title }}" class="block max-h-[55vh] w-auto max-w-full">
                    </a>
                @endif
            </div>

            <div class="px-6 py-3 border-t text-right">
                <button type="button" onclick="closeAnnouncement('ann-{{ $a->id }}')" class="px-4 py-2 rounded border hover:bg-gray-50">Close</button>
            </div>
        </div>
    </div>
@endforeach

<script>
    const bellCsrf = '{{ csrf_token() }}';

    function toggleBell() {
        document.getElementById('bell-dropdown').classList.toggle('hidden');
        document.getElementById('account-dropdown')?.classList.add('hidden');
    }

    // Update the orange badge, "X new" and "Mark all as read" after reading
    function refreshBellCount() {
        const left = document.querySelectorAll('.bell-item[data-new="1"]').length;
        const badge = document.getElementById('bell-badge');
        if (badge) { badge.textContent = left > 9 ? '9+' : left; badge.classList.toggle('hidden', left === 0); }
        const count = document.getElementById('bell-new-count');
        count.textContent = left + ' new';
        count.classList.toggle('hidden', left === 0);
        document.getElementById('bell-read-all').classList.toggle('hidden', left === 0);
    }

    // Remove the orange look from one item
    function markItemRead(item) {
        item.dataset.new = '0';
        item.classList.remove('bg-orange-50');
        item.querySelector('.bell-dot').classList.replace('bg-orange-500', 'bg-transparent');
    }

    function sendRead(url) {
        fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': bellCsrf, 'Accept': 'application/json' } });
    }

    function openAnnouncement(id, annId) {
        const item = document.querySelector('.bell-item[data-ann="' + annId + '"]');
        if (item && item.dataset.new === '1') {
            markItemRead(item);
            refreshBellCount();
            sendRead('{{ url('/notifications') }}/' + annId + '/read');
        }
        document.getElementById('bell-dropdown').classList.add('hidden');
        document.getElementById(id).classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function markAllRead() {
        document.querySelectorAll('.bell-item[data-new="1"]').forEach(markItemRead);
        refreshBellCount();
        sendRead('{{ route('notifications.read-all') }}');
    }

    function closeAnnouncement(id) {
        document.getElementById(id).classList.add('hidden');
        document.body.style.overflow = '';
    }

    // Close the list when clicking elsewhere; Esc closes everything
    document.addEventListener('click', e => {
        const menu = document.getElementById('bell-menu');
        if (menu && !menu.contains(e.target)) document.getElementById('bell-dropdown').classList.add('hidden');
    });
    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;
        document.getElementById('bell-dropdown').classList.add('hidden');
        document.querySelectorAll('.ann-modal:not(.hidden)').forEach(m => closeAnnouncement(m.id));
    });
</script>
@endif
