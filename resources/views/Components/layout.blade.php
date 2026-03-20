@props(['fullBleed' => false])
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
<nav class="bg-white/80 backdrop-blur-md border-b border-slate-900/[0.07] sticky top-0 z-50">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative flex h-14 items-center">

            {{-- Logo --}}
            <a href="/" class="flex items-center gap-2.5 group flex-shrink-0">
                <div class="w-8 h-8 rounded-xl bg-indigo-600 flex items-center justify-center
                            shadow-sm group-hover:shadow-[0_0_14px_rgba(99,102,241,0.4)]
                            transition-shadow duration-200">
                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 10h2l1 2h13l1-4H6M7 16a1 1 0 100 2 1 1 0 000-2zm10 0a1 1 0 100 2 1 1 0 000-2z"/>
                    </svg>
                </div>
                <span class="text-slate-900 font-bold text-[15px] tracking-tight">
                    Fuel<span class="text-indigo-600">Hunter</span>
                </span>
            </a>

            {{-- Desktop nav links — absolutely centred --}}
            <div class="hidden md:flex absolute inset-0 items-center justify-center pointer-events-none">
                <div class="flex items-center gap-1 pointer-events-auto">
                    <x-nav-link href="/"          :active="request()->is('/')">Map</x-nav-link>
                    <x-nav-link href="/fuel"      :active="request()->is('fuel*')">Fuel Sites</x-nav-link>
                    <x-nav-link href="/dashboard" :active="request()->is('dashboard')">Statistics</x-nav-link>
                    <x-nav-link href="/about"     :active="request()->is('about')">About</x-nav-link>
                </div>
            </div>

            {{-- Right spacer (reserved for future auth) --}}
            <div class="ml-auto hidden md:block w-32"></div>

        </div>
    </div>
</nav>

{{-- ── Page heading ──────────────────────────────────────────────────────── --}}
@if(trim($heading))
<header class="bg-white border-b border-gray-100 shadow-sm">
    <div class="mx-auto max-w-7xl px-4 py-3 sm:py-5 sm:px-6 lg:px-8">
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-gray-900">{{ $heading }}</h1>
    </div>
</header>
@endif

{{-- ── Main content ──────────────────────────────────────────────────────── --}}
<main>
    @if($fullBleed)
        {{ $slot }}
    @else
        <div class="mx-auto max-w-7xl py-3 sm:py-6 sm:px-6 lg:px-8 pb-24 md:pb-6">
            {{ $slot }}
        </div>
    @endif
</main>

{{-- ── Mobile bottom tab bar ────────────────────────────────────────────── --}}
<nav x-data="{ moreOpen: false }"
     class="md:hidden fixed bottom-0 inset-x-0 z-50 bg-white/90 backdrop-blur-md border-t border-slate-900/[0.07]"
     style="padding-bottom: env(safe-area-inset-bottom, 0px)">

    <div class="flex items-center justify-around h-16">

        {{-- Map --}}
        <a href="/"
           class="flex flex-col items-center gap-0.5 px-5 py-2 group">
            <div class="w-6 h-6 flex items-center justify-center rounded-lg transition-colors duration-150
                        {{ request()->is('/') ? 'bg-indigo-50' : '' }}">
                <svg class="w-5 h-5 transition-colors duration-150 {{ request()->is('/') ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
            </div>
            <span class="text-[10px] font-semibold leading-none transition-colors duration-150
                         {{ request()->is('/') ? 'text-indigo-600' : 'text-slate-400' }}">Map</span>
        </a>

        {{-- Dashboard --}}
        <a href="/dashboard"
           class="flex flex-col items-center gap-0.5 px-5 py-2 group">
            <div class="w-6 h-6 flex items-center justify-center rounded-lg transition-colors duration-150
                        {{ request()->is('dashboard') ? 'bg-indigo-50' : '' }}">
                <svg class="w-5 h-5 transition-colors duration-150 {{ request()->is('dashboard') ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <span class="text-[10px] font-semibold leading-none transition-colors duration-150
                         {{ request()->is('dashboard') ? 'text-indigo-600' : 'text-slate-400' }}">Stats</span>
        </a>

        {{-- Fuel Sites --}}
        <a href="/fuel"
           class="flex flex-col items-center gap-0.5 px-5 py-2 group">
            <div class="w-6 h-6 flex items-center justify-center rounded-lg transition-colors duration-150
                        {{ request()->is('fuel*') ? 'bg-indigo-50' : '' }}">
                <svg class="w-5 h-5 transition-colors duration-150 {{ request()->is('fuel*') ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <span class="text-[10px] font-semibold leading-none transition-colors duration-150
                         {{ request()->is('fuel*') ? 'text-indigo-600' : 'text-slate-400' }}">Sites</span>
        </a>

        {{-- More --}}
        <button @click="moreOpen = !moreOpen"
                class="flex flex-col items-center gap-0.5 px-5 py-2 group">
            <div class="w-6 h-6 flex items-center justify-center rounded-lg transition-colors duration-150
                        {{ request()->is('about') ? 'bg-indigo-50' : '' }}">
                <svg class="w-5 h-5 transition-colors duration-150 {{ request()->is('about') ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' }}"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/>
                </svg>
            </div>
            <span class="text-[10px] font-semibold leading-none transition-colors duration-150
                         {{ request()->is('about') ? 'text-indigo-600' : 'text-slate-400' }}">More</span>
        </button>

    </div>

    {{-- More overlay --}}
    <div x-show="moreOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         @click.away="moreOpen = false"
         class="absolute bottom-full right-0 mb-1 mr-2 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden w-44"
         style="display: none;">
        <a href="/about"
           @click="moreOpen = false"
           class="flex items-center gap-3 px-4 py-3 text-sm font-medium
                  {{ request()->is('about') ? 'text-indigo-600 bg-indigo-50' : 'text-slate-700 hover:bg-slate-50' }}
                  transition-colors duration-150">
            <svg class="w-4 h-4 {{ request()->is('about') ? 'text-indigo-500' : 'text-slate-400' }}"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            About
        </a>
    </div>

</nav>

</body>
</html>
