<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    @foreach ($colleges as $college)
        @php
            // Find the logo named after the college code, e.g. public/image/colleges/ccis.png
            $logo = null;
            foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
                $file = 'image/colleges/' . strtolower($college->code) . '.' . $ext;
                if (file_exists(public_path($file))) {
                    $logo = $file;
                    break;
                }
            }
        @endphp

        <a href="{{ route('browse.college', $college) }}"
           class="bg-white rounded-lg border p-5 hover:border-csu-600 hover:shadow transition flex flex-col items-center text-center">
            @if ($logo)
                <img src="{{ asset($logo) }}?v={{ filemtime(public_path($logo)) }}" alt="{{ $college->code }} logo" class="h-32 w-32 object-contain mb-3">
            @endif
            <p class="text-xl font-bold text-csu-800">{{ $college->code }}</p>
            <p class="text-xs text-gray-500 mt-1 leading-snug">{{ $college->name }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ $college->programs_count }} programs</p>
        </a>
    @endforeach
</div>