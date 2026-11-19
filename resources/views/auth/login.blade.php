<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · CSU Digital Library</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4"
      style="background:
             linear-gradient(rgba(17, 77, 36, 0.4), rgba(17, 77, 36, 0.4)),
             url('{{ asset('image/csu4.jpg') }}') center / cover no-repeat fixed,
             #114d24;">
    <div class="bg-white shadow-xl rounded-xl p-8 w-full max-w-sm">
       <img src="{{ asset('image/csulogo.png') }}" alt="Caraga State University logo" class="h-28 w-auto mx-auto mb-3">
        <h1 class="text-xl font-bold text-green-900 text-center">CSU Digital Library</h1>
        <p class="text-sm text-gray-500 text-center mb-6">Caraga State University – Main Campus</p>

        @if ($errors->any())
            <div class="mb-4 rounded bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ url('/login') }}">
            @csrf
            {{-- ID Number or Email (with person icon) --}}
            <label class="sr-only" for="login">ID Number or Email</label>
            <div class="relative mb-5">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12a4.5 4.5 0 100-9 4.5 4.5 0 000 9zm0 2c-4.4 0-8 2.2-8 5v2h16v-2c0-2.8-3.6-5-8-5z"/>
                </svg>
                <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus
                       placeholder="ID Number or Email"
                       class="w-full border border-gray-400 rounded-md pl-11 pr-3 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600">
            </div>

            {{-- Password (with lock icon and show/hide eye) --}}
            <label class="sr-only" for="password">Password</label>
            <div class="relative mb-4">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17 9h-1V7a4 4 0 00-8 0v2H7a2 2 0 00-2 2v9a2 2 0 002 2h10a2 2 0 002-2v-9a2 2 0 00-2-2zm-7-2a2 2 0 114 0v2h-4V7zm2 10a1.8 1.8 0 110-3.6 1.8 1.8 0 010 3.6z"/>
                </svg>
                <input id="password" name="password" type="password" required
                       placeholder="Password"
                       class="w-full border border-gray-400 rounded-md pl-11 pr-11 py-3 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600">
                <button type="button" onclick="togglePassword()" title="Show / hide password"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                    {{-- eye with a line = password hidden --}}
                    <svg id="eye-closed" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/><path d="M3 3l18 18"/>
                    </svg>
                    {{-- open eye = password shown --}}
                    <svg id="eye-open" class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
            <script>
                function togglePassword() {
                    const field = document.getElementById('password');
                    const show = field.type === 'password';
                    field.type = show ? 'text' : 'password';
                    document.getElementById('eye-open').classList.toggle('hidden', !show);
                    document.getElementById('eye-closed').classList.toggle('hidden', show);
                }
            </script>

            <label class="flex items-center gap-2 text-sm text-gray-600 mb-6">
                <input type="checkbox" name="remember"> Remember me
            </label>

            <button class="w-full bg-green-800 text-white py-2 rounded font-medium hover:bg-green-700">Log In</button>
        </form>

        <p class="mt-6 text-xs text-gray-500 text-center">For authorized CSU librarians, faculty and students only.<br>No account? Please contact the University Library.</p>
    </div>
</body>
</html>
