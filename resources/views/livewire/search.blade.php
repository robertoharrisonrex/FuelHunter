<div class="space-y-5">

    {{-- ── Search bar ────────────────────────────────────────────── --}}
    <div class="relative">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
            <svg class="w-5 h-5 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
            </svg>
        </div>
        <input wire:model.live.debounce.300ms="search"
               type="search"
               placeholder="Search stations, addresses, suburbs…"
               class="w-full pl-11 pr-12 py-3.5 bg-white border border-gray-200 rounded-2xl shadow-sm
                      text-sm text-gray-900 placeholder-gray-400
                      dark:bg-slate-900 dark:border-slate-700 dark:text-slate-100 dark:placeholder-slate-500
                      focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent
                      transition-shadow duration-150">
        <div wire:loading class="absolute inset-y-0 right-0 flex items-center pr-4">
            <svg class="animate-spin w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
        </div>
    </div>

    {{-- ── Meta row ──────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between px-0.5">
        <p class="text-sm text-gray-500 dark:text-slate-400">
            <span class="font-semibold text-gray-900 dark:text-slate-100">{{ number_format($fuelSites->total()) }}</span>
            stations in Queensland
        </p>
        @if($fuelSites->total() > 0)
            <p class="text-xs text-gray-400 dark:text-slate-500">
                {{ $fuelSites->firstItem() }}–{{ $fuelSites->lastItem() }} of {{ number_format($fuelSites->total()) }}
            </p>
        @endif
    </div>

    {{-- ── Cards grid ────────────────────────────────────────────── --}}
    @if($fuelSites->isEmpty())
        <div class="py-16 text-center">
            <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-gray-300 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-900 dark:text-slate-100 mb-1">No stations found</p>
            <p class="text-xs text-gray-400 dark:text-slate-500">Try a different name or address</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($fuelSites as $fuelSite)
                @php
                    $brandName   = $fuelSite->brand?->name ?? $fuelSite->name;
                    $initial     = strtoupper(substr(trim($brandName), 0, 1));
                    $palette     = ['bg-indigo-600','bg-teal-600','bg-amber-500','bg-rose-500','bg-violet-600','bg-emerald-600','bg-orange-500','bg-sky-600','bg-pink-600','bg-cyan-600'];
                    $avatarColor = $palette[abs(crc32($brandName)) % count($palette)];
                    $unleaded    = $fuelSite->prices->first();
                @endphp

                <a href="/fuel/{{ $fuelSite->id }}"
                   class="group flex items-center gap-4 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-4
                          hover:border-indigo-200 hover:shadow-md hover:-translate-y-px
                          transition-all duration-200 cursor-pointer">

                    {{-- Brand avatar --}}
                    <div class="flex-shrink-0 w-11 h-11 rounded-xl {{ $avatarColor }} flex items-center justify-center
                                text-white font-bold text-base shadow-sm select-none">
                        {{ $initial }}
                    </div>

                    {{-- Station info --}}
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-gray-900 dark:text-slate-100 truncate group-hover:text-indigo-600 transition-colors duration-150">
                            {{ $fuelSite->name }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-slate-500 truncate mt-0.5">
                            {{ $fuelSite->address }}{{ $fuelSite->suburb ? ', ' . $fuelSite->suburb->name : '' }}, {{ $fuelSite->postcode }}
                        </p>
                        <div class="flex items-center gap-1 mt-1">
                            <svg class="w-3 h-3 text-gray-300 dark:text-slate-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-[11px] text-gray-400 dark:text-slate-500">
                                {{ $fuelSite->city?->name ?? '' }}{{ $fuelSite->state ? ' · ' . ucwords(strtolower($fuelSite->state->name)) : '' }}
                            </span>
                        </div>
                    </div>

                    {{-- Right: price + chevron --}}
                    <div class="flex-shrink-0 flex flex-col items-end gap-2">
                        @if($unleaded)
                            <span class="inline-flex items-center bg-emerald-50 text-emerald-700 border border-emerald-100
                                         dark:bg-emerald-950 dark:text-emerald-400 dark:border-emerald-900
                                         text-xs font-bold px-2.5 py-1 rounded-full leading-none">
                                ${{ number_format($unleaded->price / 100, 3) }}
                            </span>
                        @else
                            <span class="inline-flex items-center bg-gray-50 text-gray-400 border border-gray-100
                                         dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700
                                         text-xs font-medium px-2.5 py-1 rounded-full leading-none">
                                No price
                            </span>
                        @endif
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-indigo-400 transition-colors duration-150"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>

                </a>
            @endforeach
        </div>
    @endif

    {{-- ── Pagination ─────────────────────────────────────────────── --}}
    @if($fuelSites->hasPages())
        <div class="pt-2">
            {{ $fuelSites->links() }}
        </div>
    @endif

</div>
