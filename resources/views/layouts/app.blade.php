<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · CSU Digital Library</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { csu: { 50:'#eef7f0', 100:'#d6eddc', 600:'#1f7a3a', 700:'#17632e', 800:'#114d24', 900:'#0c3a1b' } } } } };
    </script>
    <style>
        @media print { .no-print { display: none !important; } main { padding: 0 !important; } }
        
        /* Collapsible sidebar */
        #sidebar { transition: width .25s ease, padding .25s ease, opacity .2s ease; white-space: nowrap; overflow: hidden; }
        #sidebar.sidebar-collapsed { width: 0 !important; padding-left: 0 !important; padding-right: 0 !important; border-right-width: 0 !important; opacity: 0; }
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
        if ($user->isFaculty() || $user->isStudent()) {
        $links[] = ['My Classes', route('classes.index'), ['classes.*', 'faculty.classes.*']];
    }
    if ($user->isFaculty()) {
                    $links[] = ['My Recommendations', route('faculty.recommendations.index'), 'faculty.recommendations.*'];
    }

         if ($user->isAdmin()) {
        $links = [['User Accounts', route('admin.users.index'), 'admin.users.*']];
    }
    
    if ($user->isLibrarian()) {
        $pending = \App\Models\Recommendation::where('status', 'pending')->count();
        $links[] = ['Review Recommendations', route('librarian.reviews.index'), 'librarian.reviews.*', $pending];
        $links[] = ['Manage Resources', route('librarian.resources.index'), 'librarian.resources.*'];
         $links[] = ['Classifications', route('librarian.classifications.index'), 'librarian.classifications.*'];
        $links[] = ['Announcements', route('librarian.announcements.index'), 'librarian.announcements.*'];
       // $links[] = ['User Accounts', route('librarian.users.index'), 'librarian.users.*'];
        $links[] = ['Reports & Monitoring', route('librarian.reports.index'), 'librarian.reports.*'];
    }
@endphp

<header id="app-header" class="no-print bg-csu-800 text-white border-b-[3px] border-orange-500 sticky top-0 z-50"
        style="background-image: linear-gradient(to right, rgba(17,77,36,1) 0%, rgba(17,77,36,0.40) 100%, ), url('{{ asset('image/csu head.webp') }}'); background-size: auto, auto 420px; background-position: center, center 35%; background-repeat: no-repeat;"> <div class="px-3 md:px-6 py-2.5 md:py-4 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5 md:gap-4 min-w-0">
            <button type="button" class="text-xl md:text-2xl leading-none hover:text-csu-100" onclick="toggleSidebar()" title="Show / hide menu">&#9776;</button>
            <img src="{{ asset('image/csulogo.png') }}" alt="Caraga State University logo" class="h-9 md:h-12 w-auto shrink-0">
            <div class="min-w-0">
                <p class="font-semibold leading-tight text-sm md:text-base truncate">
                    <span class="sm:hidden">CSU Digital Library</span>
                    <span class="hidden sm:inline">CSU Digital Library Repository</span>
                </p>
                <p class="text-[11px] md:text-xs text-csu-100 truncate">Caraga State University<span class="hidden sm:inline"> – Main Campus</span></p>
            </div>
        </div>
        <div class="flex items-center gap-2 md:gap-3">
        {{-- Notification bell (announcements) --}}
        @include('partials.notification-bell')

        {{-- Account icon with dropdown menu --}}
        <div class="relative" id="account-menu">
            <button type="button" onclick="document.getElementById('account-dropdown').classList.toggle('hidden')"
                    class="w-8 h-8 md:w-10 md:h-10 shrink-0 rounded-full bg-white/90 text-csu-800 flex items-center justify-center hover:bg-white shadow"
                    title="My account">
                <svg class="w-5 h-5 md:w-6 md:h-6" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2a10 10 0 100 20 10 10 0 000-20zm0 4a3.5 3.5 0 110 7 3.5 3.5 0 010-7zm0 14.2a7.9 7.9 0 01-6.1-2.9c.9-1.8 3.4-2.8 6.1-2.8s5.2 1 6.1 2.8a7.9 7.9 0 01-6.1 2.9z"/>
                </svg>
            </button>

            <div id="account-dropdown" class="hidden absolute right-0 mt-2 w-64 bg-white text-gray-800 rounded-lg shadow-lg border border-csu-700 z-50">
                <div class="px-5 py-4">
                    <p class="text-sm font-medium uppercase">{{ $user->name }}</p>
                    <p class="text-sm text-gray-500">{{ $user->id_number }}</p>
                    <span class="inline-block mt-2 bg-csu-50 text-csu-800 px-2 py-0.5 rounded-full text-xs uppercase tracking-wide">{{ $user->role }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="border-t border-csu-700"
                                      data-confirm="You will need to log in again to use the repository." data-confirm-title="Sign out?" data-confirm-button="Sign Out">
                    @csrf
                    <button class="w-full flex items-center gap-2 px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 rounded-b-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M15 4h3a2 2 0 012 2v12a2 2 0 01-2 2h-3M10 17l-5-5 5-5M5 12h11" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
        </div>
        <script>
            // Close the account menu when clicking anywhere else
            document.addEventListener('click', e => {
                if (!document.getElementById('account-menu').contains(e.target)) {
                    document.getElementById('account-dropdown').classList.add('hidden');
                }
            });
        </script>
    </div>
</header>

<div class="flex">
        <aside id="sidebar" class="app-sidebar no-print w-64 bg-white border-r min-h-[calc(100vh-56px)] p-4 lg:sticky lg:self-start lg:overflow-y-auto">
        <div class="flex items-center justify-between mb-2 px-3">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-400">Menu</span>
            <button type="button" onclick="toggleSidebar(true)" title="Hide menu"
                    class="h-7 w-7 flex items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-800 text-xl leading-none">&times;</button>
        </div>
        <nav class="space-y-1">
            @foreach ($links as $link)
    @php
        [$label, $url, $pattern] = $link;
        $badge = $link[3] ?? 0;
    @endphp
    <a href="{{ $url }}"
       class="flex items-center justify-between px-3 py-2 rounded text-sm text-csu-800 font-semibold {{ request()->routeIs($pattern) ? 'bg-csu-50' : 'hover:bg-gray-50' }}">
        <span>{{ $label }}</span>
        @if ($badge > 0)
            <span class="ml-2 h-2.5 w-2.5 rounded-full bg-orange-500" title="{{ $badge }} pending"></span>
        @endif
    </a>
@endforeach 
        </nav>
    </aside>
    
    {{-- Arrow tab on the left edge: shows the menu again --}}
    <button type="button" id="sidebar-open" onclick="toggleSidebar(false)" title="Show menu"
            class="no-print hidden fixed left-0 top-1/2 -translate-y-1/2 z-40 bg-csu-700 text-white px-1.5 py-4 rounded-r-lg shadow hover:bg-csu-800 hover:px-2.5 transition-all text-lg leading-none">&rsaquo;</button>

    <script>
        // collapsed = true hides the menu, false shows it, nothing = switch
        function toggleSidebar(collapsed) {
            const sidebar = document.getElementById('sidebar');
            if (collapsed === undefined) collapsed = !sidebar.classList.contains('sidebar-collapsed');
            sidebar.classList.toggle('sidebar-collapsed', collapsed);
            document.getElementById('sidebar-open').classList.toggle('hidden', !collapsed);
            try { localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0'); } catch (e) {}
        }
        // Remember the choice between pages. On phones the menu starts hidden.
        (function () {
            let saved = null;
            try { saved = localStorage.getItem('sidebarCollapsed'); } catch (e) {}
            const startHidden = saved === null ? window.innerWidth < 768 : saved === '1';
            if (startHidden) {
                const sidebar = document.getElementById('sidebar');
                sidebar.style.transition = 'none';
                toggleSidebar(true);
                requestAnimationFrame(() => sidebar.style.transition = '');
            }
        })();
    </script>

    <main class="flex-1 min-w-0 p-4 md:p-6">
        @include('partials.flash')
                @unless (request()->routeIs('dashboard'))
            <button type="button" onclick="goBack()"
                    class="no-print inline-flex items-center gap-1 mb-3 text-sm text-gray-600 hover:text-csu-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Back
            </button>
            <script>
                function goBack() {
                    // Go to the previous page in this system; if there is none, go to the Dashboard
                    if (document.referrer.startsWith(location.origin) && history.length > 1) {
                        history.back();
                    } else {
                        location.href = '{{ route('dashboard') }}';
                    }
                }
            </script>
        @endunless  
        @yield('content')
    </main>
</div>
@stack('scripts')
{{-- ===== System confirmation box (replaces the browser's pop-up) ===== --}}
<div id="confirm-box" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-start gap-3">
            <div id="confirm-icon" class="shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-xl"></div>
            <div>
                <h2 id="confirm-title" class="text-lg font-semibold text-gray-900"></h2>
                <p id="confirm-message" class="text-sm text-gray-600 mt-1"></p>
            </div>
        </div>
        <div class="flex justify-end gap-3 mt-6">
            <button type="button" id="confirm-cancel" class="px-4 py-2 rounded border hover:bg-gray-50">Cancel</button>
            <button type="button" id="confirm-ok" class="px-4 py-2 rounded text-white"></button>
        </div>
    </div>
</div>
<script>
    // Any <form data-confirm="message"> asks with the system box before it is sent.
    // Optional: data-confirm-title="..." data-confirm-button="Delete" data-confirm-danger (red style)
    (function () {
        const box = document.getElementById('confirm-box');
        let pendingForm = null;

        function close() { box.classList.add('hidden'); pendingForm = null; }

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form.dataset.confirm) {
                // No question first, but still deleted with a 10-second Undo
                if (form.hasAttribute('data-undo')) { e.preventDefault(); window.startUndo(form); }
                return;
            }
            e.preventDefault();
            pendingForm = form;

            const danger = form.hasAttribute('data-confirm-danger');
            document.getElementById('confirm-title').textContent = form.dataset.confirmTitle || 'Are you sure?';
            document.getElementById('confirm-message').textContent = form.dataset.confirm;
            const ok = document.getElementById('confirm-ok');
            ok.textContent = form.dataset.confirmButton || 'Confirm';
            ok.className = 'px-4 py-2 rounded text-white ' + (danger ? 'bg-red-600 hover:bg-red-700' : 'bg-csu-700 hover:bg-csu-800');
            const icon = document.getElementById('confirm-icon');
            icon.className = 'shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-xl ' + (danger ? 'bg-red-100 text-red-600' : 'bg-csu-50 text-csu-700');
            icon.textContent = danger ? '!' : '?';

            box.classList.remove('hidden');
            ok.focus();
        }, true);

        document.getElementById('confirm-ok').addEventListener('click', function () {
            const form = pendingForm;
            close();
            if (form) (form.hasAttribute('data-undo') ? window.startUndo(form) : form.submit());
        });
        document.getElementById('confirm-cancel').addEventListener('click', close);
        box.addEventListener('click', e => { if (e.target === box) close(); });          // click outside = cancel
        document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });  // Esc = cancel
    })();
</script>
{{-- ===== Phone/tablet menu: hidden by default, slides over the page ===== --}}
<div id="sidebar-backdrop" onclick="toggleSidebar(true)"></div>
<style>
    /* Never let anything make the page wider than the screen (stops the "zoomed out" look on phones) */
    html, body { max-width: 100%; overflow-x: clip; }

    @media (max-width: 767px) {
        /* Tables scroll sideways inside their box instead of squeezing the columns */
        .overflow-x-auto > table { min-width: 640px; }
        /* Status labels stay on one line ("Lacking Resources", "Needs Update", ...) */
        table td span[class*="rounded"] { white-space: nowrap; }
        /* Table headings stay on one line ("Up-to-date", "Outdated", ...) */
        table th { white-space: nowrap; padding-right: 12px; }
        /* Long headings and titles wrap instead of pushing the page wider */
        h1, h2, h3, p { overflow-wrap: anywhere; }
    }

    @media (max-width: 1023px) {
        /* The desktop "collapsed" look must not hide the phone menu */
        #sidebar.app-sidebar.sidebar-collapsed {
            width: 16rem !important; opacity: 1 !important; padding: 1rem !important; border-right-width: 1px !important;
        }
        aside.app-sidebar {
            position: fixed !important; top: 0; left: 0; bottom: 0; z-index: 70;
            width: 16rem !important; margin-left: 0 !important; overflow-y: auto;
            background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,.25);
            transform: translateX(-100%) !important; transition: transform .2s ease;
            display: block !important;
        }
        body.mobile-menu-open aside.app-sidebar { transform: translateX(0) !important; }
        #sidebar-backdrop { display: none; position: fixed; inset: 0; z-index: 65; background: rgba(0,0,0,.4); }
        body.mobile-menu-open #sidebar-backdrop { display: block; }
        #sidebar-open { display: none !important; }   /* the side arrow tab is for desktop only */
    }
</style>
<script>
    (function () {
        const isPhone = () => window.matchMedia('(max-width: 1023px)').matches;
        const desktopToggle = window.toggleSidebar;

        // ☰ and × buttons: on phones open/close the sliding menu, on desktop keep the old behaviour
        window.toggleSidebar = function (collapsed) {
            if (isPhone()) {
                document.body.classList.toggle('mobile-menu-open', collapsed === undefined ? undefined : !collapsed);
            } else if (desktopToggle) {
                desktopToggle(collapsed);
            }
        };

        // Close the menu after tapping a link in it
        document.querySelectorAll('aside.app-sidebar a').forEach(a =>
            a.addEventListener('click', () => document.body.classList.remove('mobile-menu-open')));
    })();
</script>
<style>
    /* Desktop: the menu sits right under the green header and fills the rest of the screen */
    @media (min-width: 1024px) {
        #sidebar { top: var(--header-h, 0px); height: calc(100vh - var(--header-h, 0px)); min-height: 0; }
    }
</style>
<script>
    // Measure the green header so the menu starts exactly below it
    (function () {
        const setHeaderHeight = () => document.documentElement.style.setProperty(
            '--header-h', document.getElementById('app-header').offsetHeight + 'px');
        setHeaderHeight();
        window.addEventListener('resize', setHeaderHeight);
    })();
</script>
{{-- ===== Small "Undo" notice (bottom-left) for deletes ===== --}}
<div id="undo-toast" class="hidden fixed bottom-4 left-4 z-[110] flex items-center gap-3 bg-gray-900 text-white text-sm rounded-lg shadow-lg px-4 py-2.5">
    <span id="undo-text"></span>
    <button type="button" id="undo-btn" class="font-semibold text-orange-300 hover:text-orange-200">Undo</button>
    <span id="undo-count" class="hidden"></span>
</div>
<script>
    // Deleting waits 10 seconds so it can be undone. Forms use:
    //   data-undo="message"         the text in the notice (turns the feature on)
    //   data-undo-hide="selector"   the nearest parent to hide while waiting (default: the table row)
    //   data-undo-also="selector"   other things on the page to hide too (optional)
    //   data-undo-go="url"          page to open after deleting (optional; default: refresh this page)
    (function () {
        const toast = document.getElementById('undo-toast');
        let pending = null;

        function send(form, after) {
            fetch(form.action, { method: 'POST', body: new FormData(form), keepalive: true, redirect: 'manual' })
                .finally(() => {
                    if (after === 'stay') return;
                    if (after) location.href = after; else location.reload();
                });
        }

        function finish() {
            if (!pending) return;
            clearInterval(pending.timer);
            const form = pending.form;
            pending = null;
            toast.classList.add('hidden');
            send(form, form.dataset.undoGo);   // really delete now, then refresh (or open the next page)
        }

        window.startUndo = function (form) {
            if (pending) finish();      // a previous delete is still waiting: do it now
            const items = [form.closest(form.dataset.undoHide || 'tr') || form];
            if (form.dataset.undoAlso) items.push(...document.querySelectorAll(form.dataset.undoAlso));
            items.forEach(el => el.style.display = 'none');

            let seconds = 10;
            document.getElementById('undo-text').textContent = form.dataset.undo || 'Deleted.';
            document.getElementById('undo-count').textContent = seconds;
            toast.classList.remove('hidden');

            pending = { form, items, timer: setInterval(() => {
                seconds--;
                document.getElementById('undo-count').textContent = seconds;
                if (seconds <= 0) finish();
            }, 1000) };
        };

        document.getElementById('undo-btn').addEventListener('click', () => {
            if (!pending) return;
            clearInterval(pending.timer);
            pending.items.forEach(el => el.style.display = '');   // bring it back
            pending = null;
            toast.classList.add('hidden');
        });

        // Leaving the page before 10 seconds: still delete it
        window.addEventListener('pagehide', () => { if (pending) { clearInterval(pending.timer); send(pending.form, 'stay'); pending = null; } });
    })();
</script>
</body>
</html>