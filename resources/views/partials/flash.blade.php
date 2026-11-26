{{-- Page messages (success / error). Every one fades away by itself after 5 seconds. --}}
@if (session('success'))
    <div class="flash-msg no-print mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="flash-msg no-print mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="flash-msg no-print mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-semibold mb-1">Please fix the following:</p>
        <ul class="list-disc ml-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@once
    <script>
        // After 5 seconds: fade out, then fold up so the page moves back smoothly, then remove
        setTimeout(() => {
            document.querySelectorAll('.flash-msg').forEach(box => {
                box.style.transition = 'opacity .4s ease';
                box.style.opacity = '0';
                setTimeout(() => {
                    box.style.overflow = 'hidden';
                    box.style.height = box.offsetHeight + 'px';
                    box.style.transition = 'height .3s ease, margin .3s ease, padding .3s ease';
                    requestAnimationFrame(() => {
                        box.style.height = '0';
                        box.style.marginBottom = '0';
                        box.style.paddingTop = '0';
                        box.style.paddingBottom = '0';
                        box.style.borderWidth = '0';
                    });
                    setTimeout(() => box.remove(), 350);
                }, 400);
            });
        }, 5000);
    </script>
@endonce
