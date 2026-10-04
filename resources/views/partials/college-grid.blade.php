<div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    @foreach ($colleges as $college)
        <a href="{{ route('browse.college', $college) }}" class="bg-white rounded-lg border p-5 hover:border-csu-600 hover:shadow transition">
            <p class="text-xl font-bold text-csu-800">{{ $college->code }}</p>
            <p class="text-xs text-gray-500 mt-1 leading-snug">{{ $college->name }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ $college->programs_count }} programs</p>
        </a>
    @endforeach
</div>
