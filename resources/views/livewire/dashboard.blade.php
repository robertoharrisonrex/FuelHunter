@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .dash-card {
        opacity: 0;
        animation: fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) forwards;
    }
    .dash-card:nth-child(1) { animation-delay: 0.04s; }
    .dash-card:nth-child(2) { animation-delay: 0.12s; }
    .dash-card:nth-child(3) { animation-delay: 0.20s; }
    .dash-card:nth-child(4) { animation-delay: 0.28s; }
    .dash-card:nth-child(5) { animation-delay: 0.36s; }
</style>
@endassets

@php
$activePreset = match($dateFrom) {
    \Carbon\Carbon::now()->subDays(6)->format('Y-m-d')        => '7d',
    \Carbon\Carbon::now()->subDays(29)->format('Y-m-d')       => '30d',
    \Carbon\Carbon::now()->subDays(89)->format('Y-m-d')       => '90d',
    \Carbon\Carbon::now()->subYear()->addDay()->format('Y-m-d') => '1yr',
    default => '',
};
@endphp

<div class="space-y-5">

    {{-- ── Stat Cards ───────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        {{-- Avg Price --}}
        <div class="dash-card bg-slate-900 rounded-2xl p-5 border border-white/[0.06] relative overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-indigo-500 to-violet-500 rounded-t-2xl"></div>
            <div class="absolute -top-10 -right-10 w-28 h-28 rounded-full bg-indigo-500/10 blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <div class="w-7 h-7 rounded-lg bg-indigo-500/15 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                    {{ $stats['fuel_name'] ?: 'Avg Price' }}
                </p>
            </div>
            @if($stats['avg_price'])
                <p class="text-[2.2rem] font-bold text-white leading-none tracking-tight">
                    ${{ number_format($stats['avg_price'], 3) }}<span class="text-lg text-slate-400 font-medium ml-1.5">/L</span>
                </p>
                <p class="text-xs text-slate-500 mt-2">Current average · all QLD sites</p>
            @else
                <p class="text-2xl font-bold text-slate-600">—</p>
                <p class="text-xs text-slate-600 mt-2">Select a fuel type above</p>
            @endif
        </div>

        {{-- Sites Reporting --}}
        <div class="dash-card bg-slate-900 rounded-2xl p-5 border border-white/[0.06] relative overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-teal-400 to-emerald-500 rounded-t-2xl"></div>
            <div class="absolute -top-10 -right-10 w-28 h-28 rounded-full bg-teal-500/10 blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <div class="w-7 h-7 rounded-lg bg-teal-500/15 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Sites Reporting</p>
            </div>
            <p class="text-[2.2rem] font-bold text-white leading-none tracking-tight">
                {{ $stats['site_count'] > 0 ? number_format($stats['site_count']) : '—' }}<span class="text-lg text-slate-400 font-medium ml-1.5">stations</span>
            </p>
            <p class="text-xs text-slate-500 mt-2">Active Queensland fuel sites</p>
        </div>

        {{-- Date Range --}}
        <div class="dash-card bg-slate-900 rounded-2xl p-5 border border-white/[0.06] relative overflow-hidden">
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-amber-400 to-orange-500 rounded-t-2xl"></div>
            <div class="absolute -top-10 -right-10 w-28 h-28 rounded-full bg-amber-500/10 blur-2xl pointer-events-none"></div>
            <div class="flex items-center gap-2 mb-3">
                <div class="w-7 h-7 rounded-lg bg-amber-500/15 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Date Range</p>
            </div>
            <p class="text-[2.2rem] font-bold text-white leading-none tracking-tight">
                {{ $stats['day_count'] }}<span class="text-lg text-slate-400 font-medium ml-1.5">days</span>
            </p>
            <p class="text-xs text-slate-500 mt-2">
                {{ \Carbon\Carbon::parse($dateFrom)->format('d M') }}
                &rarr;
                {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
            </p>
        </div>

    </div>

    {{-- ── Filter Card ─────────────────────────────────────────── --}}
    <div class="dash-card bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <div class="flex flex-col gap-5">

            {{-- Row 1: Date range + presets --}}
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Date Range</label>
                    <div class="flex flex-wrap items-center gap-2">

                        {{-- Presets --}}
                        <div class="flex gap-1.5">
                            @foreach(['7d' => '7D', '30d' => '30D', '90d' => '90D', '1yr' => '1YR'] as $key => $label)
                                <button wire:click="setPreset('{{ $key }}')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-all duration-150
                                               {{ $activePreset === $key
                                                   ? 'bg-indigo-600 text-white border-indigo-600 shadow-[0_0_12px_rgba(99,102,241,0.35)]'
                                                   : 'border-gray-200 bg-gray-50 text-gray-500 hover:border-indigo-300 hover:text-indigo-500 hover:bg-indigo-50' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <span class="text-gray-200 hidden sm:block">|</span>

                        <input type="date" wire:model="dateFrom"
                               class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50
                                      focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent
                                      transition-shadow duration-150">
                        <span class="text-gray-300 text-sm">→</span>
                        <input type="date" wire:model="dateTo"
                               class="border border-gray-200 rounded-xl px-3 py-2 text-sm bg-gray-50
                                      focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent
                                      transition-shadow duration-150">
                    </div>
                </div>
            </div>

            {{-- Row 2: Fuel types + apply button --}}
            <div class="flex flex-wrap items-end gap-5">
                <div class="flex-1 min-w-0">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Fuel Types</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($fuelTypes as $type)
                            <label class="relative cursor-pointer select-none">
                                <input type="checkbox"
                                       wire:model="selectedFuelTypes"
                                       value="{{ $type->id }}"
                                       class="absolute opacity-0 w-0 h-0">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                             border cursor-pointer transition-all duration-200"
                                      :class="$wire.selectedFuelTypes.includes('{{ $type->id }}')
                                          ? 'bg-indigo-600 text-white border-indigo-600 shadow-[0_0_12px_rgba(99,102,241,0.35)]'
                                          : 'border-gray-200 bg-gray-50 text-gray-500 hover:border-indigo-300 hover:text-indigo-500 hover:bg-indigo-50'">
                                    {{ $type->name }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-3 self-end flex-shrink-0">
                    <div wire:loading class="flex items-center gap-1.5 text-xs text-indigo-400 font-semibold">
                        <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        Updating…
                    </div>
                    <button wire:click="applyFilters"
                            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95
                                   text-white rounded-xl px-5 py-2.5 text-sm font-bold
                                   shadow-[0_0_18px_rgba(99,102,241,0.35)]
                                   hover:shadow-[0_0_26px_rgba(99,102,241,0.55)]
                                   transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        Apply
                    </button>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Chart Card ───────────────────────────────────────────── --}}
    <div class="dash-card relative bg-slate-900 rounded-2xl shadow-2xl overflow-hidden">

        {{-- Decorative orbs --}}
        <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-indigo-500/20 blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-16 -left-16 w-64 h-64 rounded-full bg-violet-600/15 blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-40 bg-sky-500/5 blur-3xl pointer-events-none"></div>

        <div class="relative p-6">

            {{-- Header --}}
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h2 class="text-white text-xl font-bold tracking-tight">Average Fuel Price</h2>
                    <p class="text-slate-400 text-sm mt-0.5">Cents per litre · Queensland</p>
                </div>
                <div class="flex items-center gap-2 bg-slate-800/80 backdrop-blur rounded-xl px-3 py-1.5 border border-slate-700/50">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                    </span>
                    <span class="text-xs text-slate-300 font-semibold">Live Data</span>
                </div>
            </div>

            {{-- Chart --}}
            <div wire:ignore style="position:relative; height:420px">
                <canvas id="chartFuelTrends"></canvas>
            </div>

            @if(empty($chartData['datasets']))
                <p class="text-center text-sm text-slate-500 mt-4">
                    Select at least one fuel type above to display the chart.
                </p>
            @endif

        </div>
    </div>

</div>

@script
<script>
    const PALETTE = [
        { r: 99,  g: 102, b: 241 },   // indigo
        { r: 20,  g: 184, b: 166 },   // teal
        { r: 245, g: 158, b: 11  },   // amber
        { r: 239, g: 68,  b: 68  },   // red
        { r: 168, g: 85,  b: 247 },   // violet
        { r: 34,  g: 197, b: 94  },   // emerald
        { r: 249, g: 115, b: 22  },   // orange
        { r: 236, g: 72,  b: 153 },   // pink
    ];

    function rgba(c, a) {
        return `rgba(${c.r},${c.g},${c.b},${a})`;
    }

    function buildDatasets(rawDatasets, chartArea, ctx) {
        return rawDatasets.map((ds, i) => {
            const c     = PALETTE[i % PALETTE.length];
            const color = rgba(c, 1);

            let bg = rgba(c, 0.2);
            if (chartArea && ctx) {
                const grad = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                grad.addColorStop(0,   rgba(c, 0.45));
                grad.addColorStop(0.4, rgba(c, 0.12));
                grad.addColorStop(1,   rgba(c, 0));
                bg = grad;
            }

            return {
                label:                    ds.label,
                data:                     ds.data,
                borderColor:              color,
                backgroundColor:          bg,
                borderWidth:              2.5,
                tension:                  0.42,
                fill:                     true,
                pointRadius:              4,
                pointHoverRadius:         9,
                pointBackgroundColor:     color,
                pointBorderColor:         '#0f172a',
                pointBorderWidth:         2,
                pointHoverBackgroundColor:'#fff',
                pointHoverBorderColor:    color,
                pointHoverBorderWidth:    2.5,
                spanGaps:                 false,
            };
        });
    }

    const glowPlugin = {
        id: 'glowLines',
        beforeDatasetDraw(chart, args) {
            const ctx   = chart.ctx;
            const color = chart.data.datasets[args.index].borderColor;
            ctx.save();
            ctx.shadowBlur  = 22;
            ctx.shadowColor = color;
        },
        afterDatasetDraw(chart) {
            chart.ctx.restore();
        },
    };

    const initial = @json($chartData);
    const canvas  = document.getElementById('chartFuelTrends');
    const ctx     = canvas.getContext('2d');

    const chart = new Chart(canvas, {
        type: 'line',
        data: { labels: initial.labels, datasets: [] },
        options: {
            responsive:          true,
            maintainAspectRatio: false,
            animation: {
                duration: 800,
                easing:   'easeInOutQuart',
            },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color:         '#94a3b8',
                        usePointStyle: true,
                        pointStyle:    'circle',
                        padding:       22,
                        font: { size: 12, weight: '600', family: 'system-ui, sans-serif' },
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(2,6,23,0.94)',
                    titleColor:      '#e2e8f0',
                    bodyColor:       '#94a3b8',
                    borderColor:     'rgba(99,102,241,0.35)',
                    borderWidth:     1,
                    padding:         14,
                    cornerRadius:    12,
                    callbacks: {
                        label: c => ` ${c.dataset.label}: ${c.parsed.y !== null ? c.parsed.y.toFixed(1) + ' ¢/L' : 'N/A'}`,
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        color:         '#475569',
                        maxTicksLimit: 9,
                        font: { size: 11 },
                    },
                    grid:   { color: 'rgba(148,163,184,0.05)' },
                    border: { display: false },
                },
                y: {
                    ticks: {
                        color: '#475569',
                        font:  { size: 11 },
                        callback: v => v + ' ¢',
                    },
                    grid:   { color: 'rgba(148,163,184,0.05)' },
                    border: { display: false },
                    title: {
                        display: true,
                        text:    'Avg Price (¢/L)',
                        color:   '#334155',
                        font:    { size: 11 },
                    },
                },
            },
        },
        plugins: [glowPlugin],
    });

    function hydrate(labels, datasets) {
        chart.data.labels   = labels;
        chart.data.datasets = buildDatasets(datasets, chart.chartArea, ctx);
        chart.update();
    }

    requestAnimationFrame(() =>
        requestAnimationFrame(() => hydrate(initial.labels, initial.datasets))
    );

    $wire.on('chartUpdated', ({ labels, datasets }) => hydrate(labels, datasets));
</script>
@endscript
