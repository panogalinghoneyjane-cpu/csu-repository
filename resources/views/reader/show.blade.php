@extends('layouts.app')
@section('title', $resource->title)

@php $canCopy = auth()->user()->isLibrarian(); @endphp

@push('head')
@if ($canCopy)
    {{-- Librarian: selectable text layer on top of each PDF page --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf_viewer.min.css">
@endif
<style>
    /* Controlled viewing: no text selection, no printing of the reader (students and faculty) */
    @unless ($canCopy)
    #reader { user-select: none; -webkit-user-select: none; }
    @endunless
    .page-wrap .textLayer { z-index: 1; }
    #reader { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    #reader canvas { display: block; width: 100%; height: auto; margin: 0 0 16px; box-shadow: 0 1px 4px rgba(0,0,0,.15); }
    .page-wrap { position: relative; }
    .watermark { position: absolute; inset: 0; pointer-events: none; overflow: hidden; display: flex; flex-wrap: wrap;
                 align-content: space-around; justify-content: space-around; opacity: .13; }
    .watermark span { transform: rotate(-30deg); font-size: 15px; font-weight: 600; color: #000; white-space: nowrap; padding: 40px 10px; }
    @unless ($canCopy)
    @media print { body * { display: none !important; } body::after { content: 'Printing is disabled for protected library resources.'; display: block; } }
    @endunless
</style>
@endpush

@section('content')
<nav class="text-sm text-gray-500 mb-2">
    <a href="{{ route('browse.program', $resource->subject->program) }}" class="hover:underline">{{ $resource->subject->program->college->code }} › {{ $resource->subject->program->code }}</a> ›
    <a href="{{ route('browse.subject', $resource->subject) }}" class="hover:underline">{{ $resource->subject->code }}</a>
</nav>

<div class="grid xl:grid-cols-4 gap-6">
    <section class="xl:col-span-3">
        <div class="bg-white rounded-lg border p-4 mb-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h1 class="text-xl font-bold">{{ $resource->title }}</h1>
                    <p class="text-sm text-gray-600">{{ $resource->authors }} · {{ $resource->publication_year }} · {{ $resource->resource_type }}</p>
                    @if ($resource->has_physical_copy)
                        <p class="mt-2 inline-block text-sm bg-green-50 border border-green-200 text-green-800 rounded px-3 py-1">
                            <strong>Available in the Library</strong>
                            @if ($resource->call_number) Call No.: <strong>{{ $resource->call_number }}</strong>@endif
                            @if ($resource->shelf_location) · Location: <strong>{{ $resource->shelf_location }}</strong>@endif
                        </p>
                    @endif
                </div>
                @if (auth()->user()->isLibrarian())
                    <a href="{{ route('librarian.resources.edit', $resource) }}" class="text-sm border px-3 py-1 rounded hover:bg-gray-50">Edit</a>
                @endif
            </div>
            @if ($resource->description)<p class="text-sm text-gray-700 mt-2">{{ $resource->description }}</p>@endif
            @if ($resource->isLink())
                <p class="mt-3 text-xs bg-blue-50 border border-blue-200 text-blue-800 rounded px-3 py-2">
                    Online resource. It opens on the publisher's website in a new tab. Your access is recorded.
                </p>
            @elseif (! $canCopy)
                {{-- Students and faculty: view only. (The librarian can select and copy text with the mouse, no notice needed.) --}}
                <p class="mt-3 text-xs bg-yellow-50 border border-yellow-200 text-yellow-800 rounded px-3 py-2">
                    View-only resource. Downloading, printing and copying are disabled per library policy. Your access is recorded.
                </p>
            @endif
        </div>

        @if ($resource->isLink())
            {{-- Online link resource: no PDF viewer, just open the external page --}}
            <div class="bg-white rounded-lg border p-8 text-center">
                <p class="font-semibold mb-1">This resource is available online</p>
                <p class="text-sm text-gray-500 mb-5 break-all">{{ parse_url($resource->external_url, PHP_URL_HOST) }}</p>
                <a href="{{ $resource->external_url }}" target="_blank" rel="noopener noreferrer"
                   class="inline-block bg-csu-700 text-white px-6 py-2 rounded hover:bg-csu-800">Open Resource ↗</a>
            </div>
        @else
        <div class="bg-gray-200 rounded-lg p-3 flex items-center justify-between gap-2 mb-3 text-sm">
            <div class="flex items-center gap-2">
                <button id="zoom-out" class="bg-white border rounded px-3 py-1">−</button>
                <span id="zoom-label">100%</span>
                <button id="zoom-in" class="bg-white border rounded px-3 py-1">+</button>
            </div>
            <span id="page-info" class="text-gray-600">Loading…</span>
        </div>

        <div id="reader" class="bg-gray-200 rounded-lg p-3 min-h-[400px]" @unless ($canCopy) oncontextmenu="return false" @endunless></div>
        @endif
    </section>

    <aside>
        <div class="bg-white rounded-lg border p-4">
            <h2 class="font-semibold mb-1">Related Resources</h2>
            <p class="text-xs text-gray-500 mb-3">You may also find these useful.</p>
            <div class="space-y-3">
                @forelse ($related as $r)
                    @include('partials.resource-card', ['resource' => $r, 'reason' => $r->recommend_reason])
                @empty
                    <p class="text-sm text-gray-500">No related resources yet.</p>
                @endforelse
            </div>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
@unless ($resource->isLink())
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function () {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

    const url = @json($streamUrl);
    const watermarkText = @json($watermark);
    const reader = document.getElementById('reader');
    const canCopy = @json($canCopy);
    let pdfDoc = null, scale = 1.2;

    function watermark() {
        const wm = document.createElement('div');
        wm.className = 'watermark';
        for (let i = 0; i < 12; i++) { const s = document.createElement('span'); s.textContent = watermarkText; wm.appendChild(s); }
        return wm;
    }

    async function renderAll() {
        reader.innerHTML = '';
        const ratio = window.devicePixelRatio || 1;   // sharper text on phones
        for (let n = 1; n <= pdfDoc.numPages; n++) {
            const page = await pdfDoc.getPage(n);
            const viewport = page.getViewport({ scale });
            const wrap = document.createElement('div');
            wrap.className = 'page-wrap';
            wrap.style.width = Math.floor(viewport.width) + 'px';   // real size: bigger when zoomed in
            wrap.style.margin = '0 auto';
            const canvas = document.createElement('canvas');
            canvas.width = Math.floor(viewport.width * ratio);
            canvas.height = Math.floor(viewport.height * ratio);
            wrap.appendChild(canvas);
            wrap.appendChild(watermark());
            reader.appendChild(wrap);
            await page.render({
                canvasContext: canvas.getContext('2d'),
                viewport,
                transform: ratio !== 1 ? [ratio, 0, 0, ratio, 0, 0] : null,
            }).promise;

            // Librarian only: invisible text on top of the page so it can be selected and copied
            if (canCopy) {
                const textLayer = document.createElement('div');
                textLayer.className = 'textLayer';
                textLayer.style.setProperty('--scale-factor', viewport.scale);
                wrap.appendChild(textLayer);
                await pdfjsLib.renderTextLayer({
                    textContentSource: await page.getTextContent(),
                    container: textLayer,
                    viewport,
                    textDivs: [],
                }).promise;
            }
        }
        document.getElementById('zoom-label').textContent = Math.round(scale / 1.2 * 100) + '%';
    }

    pdfjsLib.getDocument({ url, withCredentials: true }).promise
        .then(async doc => {
            pdfDoc = doc;
            document.getElementById('page-info').textContent = doc.numPages + ' pages';
            // Start at a size that fits the screen width (phones)
            const firstPage = await doc.getPage(1);
            const pageWidth = firstPage.getViewport({ scale: 1 }).width;
            const available = reader.clientWidth - 8;
            if (pageWidth * scale > available) scale = available / pageWidth;
            return renderAll();
        })
        .catch(() => { reader.innerHTML = '<p class="p-6 text-center text-red-600">Unable to load this resource. Please refresh the page.</p>'; });

    document.getElementById('zoom-in').onclick = () => { if (scale < 3) { scale += 0.2; renderAll(); } };
    document.getElementById('zoom-out').onclick = () => { if (scale > 0.4) { scale = Math.max(0.3, scale - 0.2); renderAll(); } };

    if (canCopy) {
        return;   // no copy/print blocking for the librarian (select text with the mouse, Ctrl + C)
    }

    // Students and faculty: block common save / print / copy / view-source shortcuts
    document.addEventListener('keydown', e => {
        const k = e.key.toLowerCase();
        if ((e.ctrlKey || e.metaKey) && ['s', 'p', 'c', 'u'].includes(k)) { e.preventDefault(); alert('This action is disabled for protected resources.'); }
    });
    document.addEventListener('dragstart', e => e.preventDefault());
})();
</script>
@endunless
@endpush