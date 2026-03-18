<x-layout>
    <x-slot:heading>{{ $fuelSite->name }}</x-slot:heading>

    @php
        $brandName   = $fuelSite->brand?->name ?? $fuelSite->name;
        $initial     = strtoupper(substr(trim($brandName), 0, 1));
        $palette     = ['bg-indigo-600','bg-teal-600','bg-amber-500','bg-rose-500','bg-violet-600','bg-emerald-600','bg-orange-500','bg-sky-600','bg-pink-600','bg-cyan-600'];
        $avatarColor = $palette[abs(crc32($brandName)) % count($palette)];

        // Fuel type accent colours keyed on lower-case name fragments
        $fuelColors = [
            'diesel'   => ['bg-amber-500/15',  'text-amber-400',  'border-amber-500/20',  'bg-amber-500'],
            'unleaded' => ['bg-indigo-500/15', 'text-indigo-400', 'border-indigo-500/20', 'bg-indigo-500'],
            '95'       => ['bg-violet-500/15', 'text-violet-400', 'border-violet-500/20', 'bg-violet-500'],
            '98'       => ['bg-purple-500/15', 'text-purple-400', 'border-purple-500/20', 'bg-purple-500'],
            'lpg'      => ['bg-teal-500/15',   'text-teal-400',   'border-teal-500/20',   'bg-teal-500'],
            'e10'      => ['bg-emerald-500/15','text-emerald-400','border-emerald-500/20','bg-emerald-500'],
            'e85'      => ['bg-green-500/15',  'text-green-400',  'border-green-500/20',  'bg-green-500'],
            'default'  => ['bg-slate-500/15',  'text-slate-400',  'border-slate-500/20',  'bg-slate-500'],
        ];
        function fuelAccent(string $name, array $map): array {
            $n = strtolower($name);
            foreach ($map as $key => $colors) {
                if (str_contains($n, $key)) return $colors;
            }
            return $map['default'];
        }
    @endphp

    <div class="space-y-6">

        {{-- ── Breadcrumb ───────────────────────────────────────── --}}
        <a href="/fuel"
           class="inline-flex items-center gap-1.5 text-xs text-gray-400 hover:text-indigo-500 transition-colors duration-150 group">
            <svg class="w-3.5 h-3.5 group-hover:-translate-x-0.5 transition-transform duration-150"
                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to Fuel Sites
        </a>

        {{-- ── Hero card ────────────────────────────────────────── --}}
        <div class="relative bg-slate-900 rounded-2xl overflow-hidden shadow-2xl">

            {{-- Decorative orbs --}}
            <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-indigo-500/15 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-56 h-56 rounded-full bg-violet-600/10 blur-3xl pointer-events-none"></div>

            <div class="relative px-8 py-8 flex flex-col sm:flex-row sm:items-center gap-6">

                {{-- Brand avatar --}}
                <div class="flex-shrink-0 w-16 h-16 rounded-2xl {{ $avatarColor }} flex items-center justify-center
                            text-white font-bold text-2xl shadow-lg select-none">
                    {{ $initial }}
                </div>

                {{-- Station details --}}
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        @if($fuelSite->brand?->name)
                            <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest
                                         bg-indigo-500/15 border border-indigo-500/20 px-2 py-0.5 rounded-full">
                                {{ $fuelSite->brand->name }}
                            </span>
                        @endif
                    </div>
                    <h2 class="text-white text-xl font-bold tracking-tight">{{ $fuelSite->name }}</h2>
                    <p class="text-slate-400 text-sm mt-1">
                        {{ $fuelSite->address }}{{ $fuelSite->suburb ? ', ' . $fuelSite->suburb->name : '' }}, QLD {{ $fuelSite->postcode }}
                    </p>
                    <div class="flex flex-wrap items-center gap-4 mt-3">
                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                            <svg class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $fuelSite->city?->name ?? '' }}{{ $fuelSite->state ? ' · ' . ucwords(strtolower($fuelSite->state->name)) : '' }}
                        </div>
                        @if($fuelSite->latitude && $fuelSite->longitude)
                            <div class="flex items-center gap-1.5 text-xs text-slate-600 font-mono">
                                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                                {{ number_format((float)$fuelSite->latitude, 5) }}, {{ number_format((float)$fuelSite->longitude, 5) }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Google Maps link --}}
                @if($fuelSite->latitude && $fuelSite->longitude)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $fuelSite->latitude }},{{ $fuelSite->longitude }}"
                       target="_blank" rel="noopener"
                       class="flex-shrink-0 inline-flex items-center gap-2
                              bg-white/[0.07] hover:bg-white/[0.12] border border-white/[0.08]
                              text-slate-300 hover:text-white text-xs font-semibold
                              rounded-xl px-4 py-2.5 transition-all duration-150 self-start sm:self-auto">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        Open in Maps
                    </a>
                @endif

            </div>
        </div>

        {{-- ── Current prices ───────────────────────────────────── --}}
        @if($fuelSite->prices->isNotEmpty())
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Current Prices</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach($fuelSite->prices->sortBy('fuel_id') as $price)
                        @php
                            $accent = fuelAccent($price->fuelType?->name ?? '', $fuelColors);
                        @endphp
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 relative overflow-hidden">
                            <div class="absolute inset-x-0 top-0 h-[2px] {{ $accent[3] }} rounded-t-2xl opacity-60"></div>
                            <p class="text-[10px] font-bold uppercase tracking-widest {{ $accent[1] }} mb-2">
                                {{ $price->fuelType?->name ?? 'Unknown' }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 tracking-tight leading-none">
                                ${{ number_format($price->price / 100, 3) }}
                            </p>
                            <p class="text-[10px] text-gray-400 mt-1.5 leading-none">/L</p>
                            @if($price->transaction_date_utc)
                                <p class="text-[11px] text-gray-400 mt-2">
                                    {{ \Carbon\Carbon::parse($price->transaction_date_utc)->format('d M Y') }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-6 py-8 text-center">
                <p class="text-sm text-gray-400">No current price data available for this station.</p>
            </div>
        @endif

        {{-- ── Map ─────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <iframe
                width="100%"
                height="400"
                frameborder="0"
                style="border:0; display:block;"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps/embed/v1/place?key={{ env('GOOGLE_API_TOKEN') }}&q={{ $fuelSite->latitude }},{{ $fuelSite->longitude }}&zoom=15"
                allowfullscreen>
            </iframe>
        </div>

        {{-- ── Price history ────────────────────────────────────── --}}
        @if($history->isNotEmpty())
            <div>
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Price History</h3>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100">
                                <th class="text-left px-5 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Date</th>
                                <th class="text-left px-5 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Fuel Type</th>
                                <th class="text-right px-5 py-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($history as $entry)
                                @php $accent = fuelAccent($entry->fuelType?->name ?? '', $fuelColors); @endphp
                                <tr class="hover:bg-gray-50/60 transition-colors duration-100">
                                    <td class="px-5 py-3 text-gray-500 text-xs">
                                        {{ \Carbon\Carbon::parse($entry->transaction_date_utc)->format('D, d M Y') }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <span class="inline-flex items-center text-xs font-semibold px-2.5 py-0.5 rounded-full
                                                     {{ $accent[0] }} {{ $accent[1] }} border {{ $accent[2] }}">
                                            {{ $entry->fuelType?->name ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-right font-bold text-gray-900">
                                        ${{ number_format($entry->price / 100, 3) }}<span class="text-gray-400 font-normal">/L</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </div>
</x-layout>
