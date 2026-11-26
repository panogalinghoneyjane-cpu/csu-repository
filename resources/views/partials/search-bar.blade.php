{{-- Rounded search bar with voice search (microphone) --}}
<form action="{{ route('search') }}" method="GET" class="search-bar">
    <div class="flex items-center gap-2 bg-white border border-gray-300 rounded-full pl-5 pr-2 py-1.5 shadow-sm
                hover:shadow-md focus-within:shadow-md focus-within:border-csu-600 transition">
        {{-- magnifying glass --}}
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5" stroke-linecap="round"></path>
        </svg>

        <input type="text" name="q" value="{{ $q ?? '' }}" autocomplete="off"
               placeholder="Search titles, authors, courses, or any word inside the files..."
               class="flex-1 min-w-0 bg-transparent py-2 focus:outline-none">

        {{-- microphone (hidden automatically if the browser has no voice search) --}}
        <button type="button" class="voice-btn hidden shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-csu-700 hover:bg-gray-100"
                title="Search by voice">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="9" y="3" width="6" height="11" rx="3"></rect>
                <path d="M5 11a7 7 0 0014 0M12 18v3" stroke-linecap="round"></path>
            </svg>
        </button>

        <button class="shrink-0 bg-csu-700 text-white px-5 py-2 rounded-full hover:bg-csu-800">Search</button>
    </div>
    <p class="voice-status hidden text-sm text-gray-500 mt-2 pl-5"></p>

    @if (! empty($withFilters))
        {{-- Filters (only on the Search page) --}}
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm mt-3">
            <select name="college" onchange="this.form.submit()" class="bg-white border border-gray-300 rounded-full px-4 py-2">
                <option value="">All colleges</option>
                @foreach ($colleges as $c)
                    <option value="{{ $c->id }}" @selected(($filters['college'] ?? '') == $c->id)>{{ $c->code }}</option>
                @endforeach
            </select>
            <select name="type" onchange="this.form.submit()" class="bg-white border border-gray-300 rounded-full px-4 py-2">
                <option value="">All resource types</option>
                @foreach ($types as $t)
                    <option @selected(($filters['type'] ?? '') === $t)>{{ $t }}</option>
                @endforeach
            </select>
            {{-- Format: how the resource can be read (any Type can be a PDF, a link or a printed book) --}}
            <select name="format" onchange="this.form.submit()" class="bg-white border border-gray-300 rounded-full px-4 py-2">
                <option value="">All formats</option>
                @foreach (['pdf' => '📄 PDF', 'link' => '🔗 Online link', 'physical' => 'Physical copy in the library'] as $value => $name)
                    <option value="{{ $value }}" @selected(($filters['format'] ?? '') === $value)>{{ $name }}</option>
                @endforeach
            </select>
            <select name="year" onchange="this.form.submit()" class="bg-white border border-gray-300 rounded-full px-4 py-2">
                <option value="">Any publication year</option>
                @foreach ([1, 3, 5, 10] as $n)
                    <option value="{{ now()->year - $n }}" @selected(($filters['year'] ?? '') == now()->year - $n)>Last {{ $n }} year{{ $n > 1 ? 's' : '' }}</option>
                @endforeach
            </select>
        </div>
    @endif
</form>

@once
<style>
    .voice-btn.listening { background: #fee2e2; color: #dc2626; animation: voice-pulse 1.2s infinite; }
    @keyframes voice-pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, .4); } 50% { box-shadow: 0 0 0 8px rgba(220, 38, 38, 0); } }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!Recognition) return;   // browser has no voice search (e.g. Firefox): the microphone stays hidden

    document.querySelectorAll('.search-bar').forEach(form => {
        const button = form.querySelector('.voice-btn');
        const input = form.querySelector('input[name="q"]');
        const status = form.querySelector('.voice-status');
        const say = text => { status.textContent = text; status.classList.toggle('hidden', !text); };
        button.classList.remove('hidden');

        let recognition = null;
        button.addEventListener('click', () => {
            if (recognition) { recognition.stop(); return; }   // click again to stop listening

            if (!window.isSecureContext) {
                say('Voice search needs a secure (https) connection. Ask the developer to enable HTTPS.');
                return;
            }

            recognition = new Recognition();
            recognition.lang = 'en-PH';
            recognition.interimResults = true;

            recognition.onstart = () => { button.classList.add('listening'); say('Listening... say what you want to search.'); };
            recognition.onresult = event => {
                const result = event.results[event.results.length - 1];
                input.value = result[0].transcript;
                if (result.isFinal) { say('Searching for "' + input.value + '"...'); form.submit(); }
            };
            recognition.onerror = event => {
                say(event.error === 'not-allowed'
                    ? 'Microphone is blocked. Click the lock icon in the address bar and allow the microphone.'
                    : event.error === 'no-speech' ? 'No voice heard. Click the microphone and try again.'
                    : 'Voice search did not work (' + event.error + '). Please type instead.');
            };
            recognition.onend = () => { button.classList.remove('listening'); recognition = null; };
            recognition.start();
        });
    });
});
</script>
@endonce