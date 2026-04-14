<x-layout>
    <x-slot:heading>About</x-slot:heading>

    <div class="space-y-8 max-w-4xl">

        {{-- ── Project Summary ──────────────────────────────── --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm overflow-hidden">
            <div class="bg-indigo-600 px-8 py-10 relative overflow-hidden">
                <div class="relative">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h2l1 2h13l1-4H6M7 16a1 1 0 100 2 1 1 0 000-2zm10 0a1 1 0 100 2 1 1 0 000-2z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-indigo-200 uppercase tracking-widest">FuelHunter</span>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-3 tracking-tight">Track Queensland Fuel Prices in Real Time</h2>
                    <p class="text-indigo-100 text-sm leading-relaxed max-w-2xl">
                        FuelHunter is an open-source price intelligence tool that pulls live data from the
                        <span class="text-white font-medium">Queensland Government Fuel Price Reporting Scheme</span>
                        and presents it in a clean, interactive dashboard. Whether you want to find the cheapest
                        unleaded near you or track how prices have moved over the past month, FuelHunter has you covered.
                    </p>
                </div>
            </div>

            {{-- Feature grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-slate-700">
                <div class="px-6 py-5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950 flex items-center justify-center mb-3">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-slate-100 mb-1">Live Price Trends</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">
                        Interactive charts show average prices per fuel type over any date range, updated every 24 hours.
                    </p>
                </div>
                <div class="px-6 py-5">
                    <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-950 flex items-center justify-center mb-3">
                        <svg class="w-4 h-4 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-slate-100 mb-1">Station Explorer</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">
                        Browse hundreds of Queensland fuel sites with current prices, brand, and location details.
                    </p>
                </div>
                <div class="px-6 py-5">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950 flex items-center justify-center mb-3">
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-slate-100 mb-1">Historical Data</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400 leading-relaxed">
                        Every price change is archived, giving you a full history of the Queensland fuel market.
                    </p>
                </div>
            </div>
        </div>

        {{-- ── Data Sources ──────────────────────────────────── --}}
        <div class="space-y-3">
            <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/50 rounded-2xl px-6 py-4 flex gap-4 items-start">
                <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-amber-800 dark:text-amber-300 leading-relaxed">
                    <span class="font-bold">Fuel price data:</span>
                    Sourced from the Queensland Government's Fuel Price Reporting Scheme API under
                    the Queensland Government's open data licence. FuelHunter is an independent tool and is not
                    affiliated with or endorsed by the Queensland Government.
                </p>
            </div>
            <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/50 rounded-2xl px-6 py-4 flex gap-4 items-start">
                <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <p class="text-xs text-blue-800 dark:text-blue-300 leading-relaxed">
                    <span class="font-bold">Global oil price data:</span>
                    WTI Crude, Brent Crude, Natural Gas, and Gasoline commodity prices are sourced from the
                    <span class="font-medium">OilPrice API</span> (oilpriceapi.com), updated every 20 minutes.
                    FuelHunter is not affiliated with OilPrice API.
                </p>
            </div>
        </div>

        {{-- ── Developer + Feedback (two-column) ───────────────── --}}
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6">

            {{-- Developer contact --}}
            <div class="md:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-6 flex flex-col gap-6">
                <div>
                    <h3 class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-4">Developer</h3>
                    <div class="flex flex-col gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-indigo-600 flex items-center justify-center flex-shrink-0 text-white font-bold text-sm">
                                R
                            </div>
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-slate-100">Roberto</p>
                                <p class="text-xs text-gray-400 dark:text-slate-500">Lead Developer</p>
                            </div>
                        </div>

                        <a href="mailto:roberto@boffincentral.com"
                           class="flex items-center gap-2.5 text-xs text-gray-500 dark:text-slate-400 hover:text-indigo-600 transition-colors group">
                            <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-slate-800 group-hover:bg-indigo-50 flex items-center justify-center transition-colors flex-shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            roberto@boffincentral.com
                        </a>

                        <div class="flex items-center gap-2.5 text-xs text-gray-500 dark:text-slate-400">
                            <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-slate-800 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            Brisbane, Queensland, Australia
                        </div>
                    </div>
                </div>

                {{-- Copyright --}}
                <div class="mt-auto pt-5 border-t border-gray-100 dark:border-slate-700">
                    <p class="text-[11px] text-gray-400 dark:text-slate-500 leading-relaxed">
                        &copy; {{ date('Y') }} FuelHunter. All rights reserved.<br>
                        Built with Laravel, Livewire &amp; Chart.js.
                    </p>
                </div>
            </div>

            {{-- Feedback form --}}
            <div class="md:col-span-3 bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-6">
                <h3 class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-5">Send Feedback</h3>
                <livewire:feedback-form />
            </div>

        </div>

    </div>
</x-layout>
