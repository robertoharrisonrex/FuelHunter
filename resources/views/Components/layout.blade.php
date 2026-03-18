<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>FuelHunter</title>
    @vite('resources/css/app.css')
</head>
<body class="h-full bg-slate-50">

{{-- ── Navigation ──────────────────────────────────────────────────────── --}}
<nav class="bg-slate-950 border-b border-white/[0.06] sticky top-0 z-50"
     x-data="{ mobileOpen: false }">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">

            {{-- Brand + desktop links --}}
            <div class="flex items-center gap-6">

                {{-- Logo + wordmark --}}
                <a href="/" class="flex items-center gap-2.5 group flex-shrink-0">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center
                                shadow-[0_0_18px_rgba(99,102,241,0.4)]
                                group-hover:shadow-[0_0_26px_rgba(99,102,241,0.6)]
                                transition-shadow duration-200">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M3 10h2l1 2h13l1-4H6M7 16a1 1 0 100 2 1 1 0 000-2zm10 0a1 1 0 100 2 1 1 0 000-2z"/>
                        </svg>
                    </div>
                    <span class="text-white font-bold text-[15px] tracking-tight">
                        Fuel<span class="text-indigo-400">Hunter</span>
                    </span>
                </a>

                {{-- Desktop nav links --}}
                <div class="hidden md:flex items-center gap-1">
                    <x-nav-link href="/"          :active="request()->is('/')">Map</x-nav-link>
                    <x-nav-link href="/fuel"      :active="request()->is('fuel')">Fuel Sites</x-nav-link>
                    <x-nav-link href="/dashboard" :active="request()->is('dashboard')">Dashboard</x-nav-link>
                    <x-nav-link href="/about"     :active="request()->is('about')">About</x-nav-link>
                </div>
            </div>

            {{-- Right side: auth --}}
            <div class="hidden md:flex items-center gap-2">
                @guest
                    <x-nav-link href="/login" :active="request()->is('login')">Log in</x-nav-link>
                    <a href="/register"
                       class="inline-flex items-center bg-indigo-600 hover:bg-indigo-500 active:scale-95
                              text-white text-sm font-semibold rounded-xl px-4 py-2
                              transition-all duration-150 shadow-[0_0_14px_rgba(99,102,241,0.35)]
                              hover:shadow-[0_0_20px_rgba(99,102,241,0.5)]">
                        Register
                    </a>
                @endguest

                @auth
                    <x-nav-link href="/tool"    :active="request()->is('tool')">Tools</x-nav-link>
                    <x-nav-link href="/profile" :active="request()->is('profile')">Profile</x-nav-link>

                    <form method="post" action="/logout" class="m-0">
                        @csrf
                        <button type="submit"
                                class="text-slate-400 hover:text-white hover:bg-white/[0.06]
                                       rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150">
                            Log out
                        </button>
                    </form>

                    <a href="/profile"
                       class="ml-1 flex items-center justify-center w-8 h-8 rounded-full
                              ring-2 ring-white/10 hover:ring-indigo-500/60 transition-all overflow-hidden">
                        <img class="w-full h-full object-cover"
                             src="{{ Auth::user()->logo_local == 1 ? asset('storage/' . Auth::user()->logo) : Auth::user()->logo }}"
                             alt="{{ Auth::user()->name }}">
                    </a>
                @endauth
            </div>

            {{-- Mobile hamburger --}}
            <button @click="mobileOpen = !mobileOpen"
                    class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg
                           text-slate-400 hover:text-white hover:bg-white/[0.06]
                           transition-colors duration-150">
                <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
                <svg x-show="mobileOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div x-show="mobileOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         class="md:hidden border-t border-white/[0.06]">
        <div class="px-3 py-3 space-y-0.5">
            <x-nav-link href="/"          :active="request()->is('/')"         class="block">Map</x-nav-link>
            <x-nav-link href="/fuel"      :active="request()->is('fuel')"      class="block">Fuel Sites</x-nav-link>
            <x-nav-link href="/dashboard" :active="request()->is('dashboard')" class="block">Dashboard</x-nav-link>
            <x-nav-link href="/about"     :active="request()->is('about')"     class="block">About</x-nav-link>
        </div>

        @guest
            <div class="border-t border-white/[0.06] px-3 py-3 space-y-0.5">
                <x-nav-link href="/login"    :active="request()->is('login')"    class="block">Log in</x-nav-link>
                <x-nav-link href="/register" :active="request()->is('register')" class="block">Register</x-nav-link>
            </div>
        @endguest

        @auth
            <div class="border-t border-white/[0.06] px-3 py-3 space-y-0.5">
                <x-nav-link href="/tool"    :active="request()->is('tool')"    class="block">Tools</x-nav-link>
                <x-nav-link href="/profile" :active="request()->is('profile')" class="block">Profile</x-nav-link>
                <form method="post" action="/logout" class="m-0">
                    @csrf
                    <button type="submit"
                            class="w-full text-left text-slate-400 hover:text-white hover:bg-white/[0.06]
                                   rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150">
                        Log out
                    </button>
                </form>
            </div>
        @endauth
    </div>
</nav>

{{-- ── Page heading ──────────────────────────────────────────────────────── --}}
<header class="bg-white border-b border-gray-100 shadow-sm">
    <div class="mx-auto max-w-7xl px-4 py-3 sm:py-5 sm:px-6 lg:px-8">
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900">{{ $heading }}</h1>
    </div>
</header>

{{-- ── Main content ──────────────────────────────────────────────────────── --}}
<main>
    <div class="mx-auto max-w-7xl py-3 sm:py-6 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>
</main>

</body>
</html>
