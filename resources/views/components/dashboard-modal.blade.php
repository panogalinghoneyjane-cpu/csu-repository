{{-- Pop-up window used by the librarian Dashboard boxes --}}
@props(['id', 'title', 'subtitle' => '', 'proceed', 'proceedLabel' => 'Proceed'])

<div id="{{ $id }}" class="dashboard-modal hidden fixed inset-0 z-[90] bg-black/40 flex items-center justify-center p-4"
     onclick="if (event.target === this) closeModal('{{ $id }}')">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col">
        {{-- Title --}}
        <div class="flex items-start justify-between gap-4 px-6 pt-5 pb-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $title }}</h2>
                @if ($subtitle)<p class="text-sm text-gray-500">{{ $subtitle }}</p>@endif
            </div>
            <button type="button" onclick="closeModal('{{ $id }}')" class="text-2xl leading-none text-gray-400 hover:text-gray-700" title="Close">&times;</button>
        </div>

        {{-- List --}}
        <div class="px-6 pb-2 overflow-y-auto text-sm">
            {{ $slot }}
        </div>

        {{-- Buttons --}}
        <div class="flex justify-end gap-3 px-6 py-4 border-t">
            <button type="button" onclick="closeModal('{{ $id }}')" class="px-4 py-2 rounded border hover:bg-gray-50">Close</button>
            <a href="{{ $proceed }}" class="px-4 py-2 rounded bg-csu-700 text-white hover:bg-csu-800">{{ $proceedLabel }} →</a>
        </div>
    </div>
</div>