<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · CSU Digital Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { csu: { 50:'#eef7f0', 100:'#d6eddc', 600:'#1f7a3a', 700:'#17632e', 800:'#114d24', 900:'#0c3a1b' } } } } };
    </script>
    <style>
        @media print { .no-print { display: none !important; } main { padding: 0 !important; } }
    </style>
    @stack('head')
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen">
@php
    $user = auth()->user();
    $links = [
        ['Dashboard', route('dashboard'), 'dashboard'],
        ['Browse Collection', route('browse.colleges'), 'browse.*'],
        ['Search', route('search'), 'search'],
    ];
    if ($user->isFaculty()) {
        $links[] = ['Recommend a Resource', route('faculty.recommendations.create'), 'faculty.recommendations.create'];
        $links[] = ['My Recommendations', route('faculty.recommendations.index'), 'faculty.recommendations.index'];
    }
    if ($user->isLibrarian()) {
        $pending = \App\Models\Recommendation::where('status', 'pending')->count();
        $links[] = ['Review Recommendations'.($pending ? " ($pending)" : ''), route('librarian.reviews.index'), 'librarian.reviews.*'];
        $links[] = ['Manage Resources', route('librarian.resources.index'), 'librarian.resources.*'];
        $links[] = ['Classifications', route('librarian.classifications.index'), 'librarian.classifications.*'];
        $links[] = ['User Accounts', route('librarian.users.index'), 'librarian.users.*'];
        $links[] = ['Reports & Monitoring', route('librarian.reports.index'), 'librarian.reports.*'];
    }
@endphp

<header class="no-print bg-csu-800 text-white">
    <div class="px-4 md:px-6 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <button type="button" class="md:hidden text-2xl leading-none" onclick="document.getElementById('sidebar').classList.toggle('hidden')">&#9776;</button>
            <div>
                <p class="font-semibold leading-tight">CSU Digital Library Repository</p>
                <p class="text-xs text-csu-100">Caraga State University – Main Campus</p>
            </div>
        </div>
        <div class="flex items-center gap-3 text-sm">
            <div class="text-right hidden sm:block">
                <p class="font-medium">{{ $user->name }}</p>
                <p class="text-xs text-csu-100">{{ $user->id_number }}</p>
            </div>
            <span class="bg-csu-600 px-3 py-1 rounded-full text-xs uppercase tracking-wide">{{ $user->role }}</span>
        </div>
    </div>
</header>

<div class="flex">
    <aside id="sidebar" class="no-print hidden md:block w-64 shrink-0 bg-white border-r min-h-[calc(100vh-56px)] p-4">
        <nav class="space-y-1">
            @foreach ($links as [$label, $url, $pattern])
                <a href="{{ $url }}"
                   class="block px-3 py-2 rounded text-sm {{ request()->routeIs($pattern) ? 'bg-csu-50 text-csu-800 font-semibold' : 'hover:bg-gray-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </nav>
        <form method="POST" action="{{ route('logout') }}" class="mt-8 border-t pt-4">
            @csrf
            <button class="w-full text-left px-3 py-2 rounded text-sm text-red-600 hover:bg-red-50">Log out</button>
        </form>
    </aside>

    <main class="flex-1 min-w-0 p-4 md:p-6">
        @include('partials.flash')
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
