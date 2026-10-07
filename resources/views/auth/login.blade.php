<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · CSU Digital Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4"
      style="background:
             linear-gradient(rgba(17, 77, 36, 0.4), rgba(17, 77, 36, 0.4)),
             url('{{ asset('image/csu4.jpg') }}') center / cover no-repeat fixed,
             #114d24;">
    <div class="bg-white shadow-xl rounded-xl p-8 w-full max-w-sm">
       <img src="{{ asset('image/csulogo.png') }}" alt="Caraga State University logo" class="mx-auto mb-3 h-20 w-20 object-contain">
        <h1 class="text-xl font-bold text-green-900 text-center">CSU Digital Library</h1>
        <p class="text-sm text-gray-500 text-center mb-6">Caraga State University – Main Campus</p>

        @if ($errors->any())
            <div class="mb-4 rounded bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ url('/login') }}">
            @csrf
            <label class="block text-sm font-medium mb-1" for="login">ID Number or Email</label>
            <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus
                   class="w-full border rounded px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-green-600">

            <label class="block text-sm font-medium mb-1" for="password">Password</label>
            <input id="password" name="password" type="password" required
                   class="w-full border rounded px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-green-600">

            <label class="flex items-center gap-2 text-sm text-gray-600 mb-6">
                <input type="checkbox" name="remember"> Remember me
            </label>

            <button class="w-full bg-green-800 text-white py-2 rounded font-medium hover:bg-green-700">Log In</button>
        </form>

        <p class="mt-6 text-xs text-gray-500 text-center">For authorized CSU librarians, faculty and students only.<br>No account? Please contact the University Library.</p>
    </div>
</body>
</html>
