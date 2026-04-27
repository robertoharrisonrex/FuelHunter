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
    .dash-card:nth-child(6) { animation-delay: 0.44s; }
    .dash-card:nth-child(7) { animation-delay: 0.52s; }
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
        <div class="dash-card bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 shadow-sm border-t-[3px] border-t-indigo-500 relative overflow-hidden">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                    {{ $stats['fuel_name'] ?: 'Avg Price' }}
                </p>
            </div>
            @if($stats['avg_price'])
                <p class="text-[2.2rem] font-bold text-slate-900 dark:text-slate-100 leading-none tracking-tight">
                    ${{ number_format($stats['avg_price'], 3) }}<span class="text-lg text-slate-400 dark:text-slate-500 font-medium ml-1.5">/L</span>
                </p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Current average · all QLD sites</p>
            @else
                <p class="text-2xl font-bold text-slate-400 dark:text-slate-500">—</p>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Select a fuel type above</p>
            @endif
        </div>

        {{-- Sites Reporting --}}
        <div class="dash-card bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 shadow-sm border-t-[3px] border-t-sky-500 relative overflow-hidden">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-7 h-7 rounded-lg bg-sky-50 dark:bg-sky-950 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Sites Reporting</p>
            </div>
            <p class="text-[2.2rem] font-bold text-slate-900 dark:text-slate-100 leading-none tracking-tight">
                {{ $stats['site_count'] > 0 ? number_format($stats['site_count']) : '—' }}<span class="text-lg text-slate-400 dark:text-slate-500 font-medium ml-1.5">stations</span>
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Active Queensland fuel sites</p>
        </div>

        {{-- Date Range --}}
        <div class="dash-card bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-700 shadow-sm border-t-[3px] border-t-amber-500 relative overflow-hidden">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-950 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">Date Range</p>
            </div>
            <p class="text-[2.2rem] font-bold text-slate-900 dark:text-slate-100 leading-none tracking-tight">
                {{ $stats['day_count'] }}<span class="text-lg text-slate-400 dark:text-slate-500 font-medium ml-1.5">days</span>
            </p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
                {{ \Carbon\Carbon::parse($dateFrom)->format('d M') }}
                &rarr;
                {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
            </p>
        </div>

    </div>

    {{-- ── Filter Card ─────────────────────────────────────────── --}}
    <div class="dash-card bg-white dark:bg-slate-900 rounded-2xl border border-gray-100 dark:border-slate-700 shadow-sm p-5">
        <div class="flex flex-col gap-5">

            {{-- Row 1: Date range + presets --}}
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">Date Range</label>
                    <div class="flex flex-wrap items-center gap-2">

                        {{-- Presets --}}
                        <div class="flex gap-1.5">
                            @foreach(['7d' => '7D', '30d' => '30D', '90d' => '90D', '1yr' => '1YR'] as $key => $label)
                                <button wire:click="setPreset('{{ $key }}')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-all duration-150
                                               {{ $activePreset === $key
                                                   ? 'bg-indigo-600 text-white border-indigo-600'
                                                   : 'border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-slate-400 hover:border-indigo-300 hover:text-indigo-500 hover:bg-indigo-50' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <span class="text-gray-200 dark:text-slate-600 hidden sm:block">|</span>

                        <input type="date" wire:model="dateFrom"
                               class="border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2 text-sm bg-gray-50 dark:bg-slate-800
                                      text-gray-900 dark:text-slate-100
                                      focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent
                                      transition-shadow duration-150">
                        <span class="text-gray-300 dark:text-slate-600 text-sm">→</span>
                        <input type="date" wire:model="dateTo"
                               class="border border-gray-200 dark:border-slate-600 rounded-xl px-3 py-2 text-sm bg-gray-50 dark:bg-slate-800
                                      text-gray-900 dark:text-slate-100
                                      focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent
                                      transition-shadow duration-150">
                    </div>
                </div>
            </div>

            {{-- Row 2: Fuel types + apply button --}}
            <div class="flex flex-wrap items-end gap-5">
                <div class="flex-1 min-w-0">
                    <label class="block text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">Fuel Types</label>
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
                                          ? 'bg-indigo-600 text-white border-indigo-600'
                                          : 'border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800 text-gray-500 dark:text-slate-400 hover:border-indigo-300 hover:text-indigo-500 hover:bg-indigo-50'">
                                    {{ $type->name }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-3 self-end flex-shrink-0">
                    <div wire:loading style="display:none" class="flex items-center gap-1.5 text-xs text-indigo-400 font-semibold">
                        <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        Updating…
                    </div>
                    <button wire:click="applyFilters"
                            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 active:scale-95
                                   text-white rounded-xl px-5 py-2.5 text-sm font-bold
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
    <div class="dash-card bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">

        <div class="p-6">

            {{-- Header --}}
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h2 class="text-slate-900 dark:text-slate-100 text-xl font-bold tracking-tight">Average Fuel Price</h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Cents per litre · Queensland</p>
                </div>
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 rounded-xl px-3 py-1.5 border border-slate-200 dark:border-slate-700">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                    </span>
                    <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">Live Data</span>
                </div>
            </div>

            {{-- Chart --}}
            <div wire:ignore class="relative h-[260px] sm:h-[420px]">
                <canvas id="chartFuelTrends"></canvas>
            </div>

            @if(empty($chartData['datasets']))
                <p class="text-center text-sm text-slate-400 dark:text-slate-500 mt-4">
                    Select at least one fuel type above to display the chart.
                </p>
            @endif

        </div>
    </div>

    {{-- ── Brand Market Share ──────────────────────────────────────── --}}
    <div class="dash-card bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h2 class="text-slate-900 dark:text-slate-100 text-xl font-bold tracking-tight">Brand Market Share</h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Share of total Queensland fuel sites by brand</p>
                </div>
            </div>
            <div wire:ignore class="flex flex-col sm:flex-row items-center gap-6">
                <div class="relative w-[280px] h-[280px] flex-shrink-0">
                    <canvas id="chartBrandShare"></canvas>
                </div>
                <div id="brandShareLegend" class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-600"></div>
            </div>
            @if(empty($brandShare['labels']))
                <p class="text-center text-sm text-slate-400 dark:text-slate-500 mt-4">No brand data available.</p>
            @endif
        </div>
    </div>

    {{-- ── Global Oil Prices ──────────────────────────────────────── --}}
    <div class="dash-card bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden">
        <div wire:ignore class="p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-slate-900 dark:text-slate-100 text-xl font-bold tracking-tight">Global Oil Prices</h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">USD — last 72 hours</p>
                </div>
                <div id="oilStatusBadge"></div>
            </div>

            {{-- Series toggles --}}
            <div class="flex flex-wrap gap-2 mb-4" id="oilToggles">
                <button data-code="WTI_USD"          class="oil-toggle px-3 py-1 rounded-full text-xs font-semibold border transition-colors">WTI Crude</button>
                <button data-code="BRENT_CRUDE_USD"  class="oil-toggle px-3 py-1 rounded-full text-xs font-semibold border transition-colors">Brent Crude</button>
                <button data-code="NATURAL_GAS_USD"  class="oil-toggle px-3 py-1 rounded-full text-xs font-semibold border transition-colors">Natural Gas</button>
                <button data-code="GASOLINE_USD"     class="oil-toggle px-3 py-1 rounded-full text-xs font-semibold border transition-colors">Gasoline</button>
            </div>

            <div class="relative h-[200px] sm:h-[300px]">
                <canvas id="chartOilPrices"></canvas>
            </div>
            <p id="oilPricesEmpty" class="hidden text-center text-sm text-slate-400 dark:text-slate-500 mt-4">
                No oil price data available yet — check back after the ETL has run.
            </p>
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

    function getChartTheme() {
        const dark = document.documentElement.classList.contains('dark');
        return {
            gridColor:    dark ? 'rgba(255,255,255,0.06)' : 'rgba(15,23,42,0.05)',
            tickColor:    dark ? '#94a3b8' : '#475569',
            legendColor:  dark ? '#94a3b8' : '#64748b',
            yTitleColor:  dark ? '#94a3b8' : '#334155',
            // Tooltip uses a fixed dark-glass style on both modes
            tooltipBg:    'rgba(2,6,23,0.44)',
            tooltipTitle: '#e2e8f0',
            tooltipBody:  '#94a3b8',
            pieBorder:    dark ? '#0f172a' : '#ffffff',
            legendText:   dark ? '#94a3b8' : '#64748b',
            legendMuted:  dark ? '#475569' : '#94a3b8',
            legendCount:  dark ? '#334155' : '#cbd5e1',
        };
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
                tension:                  0,
                fill:                     true,
                pointRadius:              0,
                pointHoverRadius:         4,
                pointBackgroundColor:     color,
                pointBorderColor:         '#ffffff',
                pointBorderWidth:         2,
                pointHoverBackgroundColor:'#fff',
                pointHoverBorderColor:    color,
                pointHoverBorderWidth:    2.5,
                spanGaps:                 true,
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

    const t = getChartTheme();
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
                        color:         t.legendColor,
                        usePointStyle: true,
                        pointStyle:    'circle',
                        padding:       22,
                        font: { size: 12, weight: '600', family: 'system-ui, sans-serif' },
                    },
                },
                tooltip: {
                    backgroundColor: t.tooltipBg,
                    titleColor:      t.tooltipTitle,
                    bodyColor:       t.tooltipBody,
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
                        color:         t.tickColor,
                        maxTicksLimit: 9,
                        font: { size: 11 },
                    },
                    grid:   { color: t.gridColor },
                    border: { display: false },
                },
                y: {
                    ticks: {
                        color: t.tickColor,
                        font:  { size: 11 },
                        callback: v => v + ' ¢',
                    },
                    grid:   { color: t.gridColor },
                    border: { display: false },
                    title: {
                        display: true,
                        text:    'Avg Price (¢/L)',
                        color:   t.yTitleColor,
                        font:    { size: 11 },
                    },
                },
            },
        },
        plugins: [],
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

    // ── Brand Market Share (pie) ───────────────────────────────────
    const PIE_PALETTE = [
        '#6366f1', '#14b8a6', '#f59e0b', '#ef4444',
        '#a855f7', '#22c55e', '#f97316', '#ec4899',
        '#06b6d4', '#84cc16', '#8b5cf6', '#f43f5e',
    ];

    const shareInitial  = @json($brandShare);
    const shareCanvas   = document.getElementById('chartBrandShare');
    const shareLegendEl = document.getElementById('brandShareLegend');

    function buildLegend(labels, values, counts) {
        const t = getChartTheme();
        shareLegendEl.innerHTML = labels.map((label, i) => `
            <div class="flex items-center gap-1.5 whitespace-nowrap">
                <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0"
                      style="background:${PIE_PALETTE[i % PIE_PALETTE.length]}"></span>
                <span style="font-weight:600;color:${t.legendText}">${label}</span>
                <span style="color:${t.legendMuted}">${values[i]}%</span>
                <span style="color:${t.legendCount};font-size:0.75rem">(${counts[i]})</span>
            </div>
        `).join('');
    }

    const tShare = getChartTheme();
    const brandShareChart = new Chart(shareCanvas, {
        type: 'doughnut',
        data: {
            labels:   shareInitial.labels,
            datasets: [{
                data:            shareInitial.values,
                backgroundColor: shareInitial.labels.map((_, i) => PIE_PALETTE[i % PIE_PALETTE.length]),
                borderColor:     tShare.pieBorder,
                borderWidth:     2,
                hoverOffset:     8,
            }],
        },
        options: {
            responsive:          true,
            maintainAspectRatio: false,
            cutout:              '62%',
            animation:           { duration: 800, easing: 'easeInOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: tShare.tooltipBg,
                    titleColor:      tShare.tooltipTitle,
                    bodyColor:       tShare.tooltipBody,
                    borderColor:     'rgba(99,102,241,0.35)',
                    borderWidth:     1,
                    padding:         14,
                    cornerRadius:    12,
                    callbacks: {
                        label: c => {
                            const count = shareInitial.counts[c.dataIndex] ?? '';
                            return ` ${c.label}: ${c.parsed}% (${count} sites)`;
                        },
                    },
                },
            },
        },
    });

    buildLegend(shareInitial.labels, shareInitial.values, shareInitial.counts);

    $wire.on('brandShareUpdated', ({ labels, values, counts }) => {
        brandShareChart.data.labels              = labels;
        brandShareChart.data.datasets[0].data    = values;
        brandShareChart.data.datasets[0].backgroundColor = labels.map((_, i) => PIE_PALETTE[i % PIE_PALETTE.length]);
        brandShareChart.options.plugins.tooltip.callbacks.label = c => {
            const count = counts[c.dataIndex] ?? '';
            return ` ${c.label}: ${c.parsed}% (${count} sites)`;
        };
        brandShareChart.update();
        buildLegend(labels, values, counts);
    });

    // ── Global Oil Prices chart ───────────────────────────────────
    let OIL_COLOURS = {
        WTI_USD:         '#f59e0b',
        BRENT_CRUDE_USD: '#3b82f6',
        NATURAL_GAS_USD: '#10b981',
        GASOLINE_USD:    '#8b5cf6',
    };
    let OIL_LABELS = {
        WTI_USD:         'WTI Crude',
        BRENT_CRUDE_USD: 'Brent Crude',
        NATURAL_GAS_USD: 'Natural Gas',
        GASOLINE_USD:    'Gasoline',
    };
    let OIL_ACTIVE_CODE = 'WTI_USD';

    let oilChartRef   = null;
    let oilToggleBtns = null;

    function applyOilToggleStyle(btn, active) {
        const colour = OIL_COLOURS[btn.dataset.code];
        const dark   = document.documentElement.classList.contains('dark');
        btn.style.cssText = active
            ? `background:${colour}1a;border-color:${colour};color:${colour}`
            : dark
                ? 'background:#1e293b;border-color:#334155;color:#64748b'
                : 'background:#f8fafc;border-color:#e2e8f0;color:#94a3b8';
    }

    function buildOilDatasets(series) {
        return Object.entries(series).map(([code, values]) => ({
            label:           OIL_LABELS[code] ?? code,
            data:            values,
            borderColor:     OIL_COLOURS[code],
            backgroundColor: OIL_COLOURS[code] + '1a',
            borderWidth:     2,
            pointRadius:     0,
            tension:         0,
            fill:            false,
            spanGaps:        false,
            hidden:          code !== OIL_ACTIVE_CODE,
        }));
    }

    async function initOilChart() {
        let data;
        try {
            const resp = await fetch('/oil-prices');
            data = await resp.json();
        } catch (e) {
            document.getElementById('chartOilPrices').closest('.relative').classList.add('hidden');
            document.getElementById('oilPricesEmpty').classList.remove('hidden');
            return;
        }

        // Render Live / Market Closed badge
        const badgeEl = document.getElementById('oilStatusBadge');
        if (badgeEl) {
            if (data.market_open) {
                badgeEl.innerHTML = `
                    <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 rounded-xl px-3 py-1.5 border border-slate-200 dark:border-slate-700">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                        </span>
                        <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">Live</span>
                    </div>`;
            } else {
                badgeEl.innerHTML = `
                    <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 rounded-xl px-3 py-1.5 border border-slate-200 dark:border-slate-700">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-400"></span>
                        </span>
                        <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">Market Closed</span>
                    </div>`;
            }
        }

        if (!data.dates || data.dates.length === 0) {
            document.getElementById('chartOilPrices').closest('.relative').classList.add('hidden');
            document.getElementById('oilPricesEmpty').classList.remove('hidden');
            return;
        }

        const canvas = document.getElementById('chartOilPrices');
        const ctx    = canvas.getContext('2d');

        const t = getChartTheme();
        oilChartRef = new Chart(ctx, {
            type: 'line',
            data: {
                labels:   data.dates,
                datasets: buildOilDatasets(data.series),
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                animation:           { duration: 600 },
                interaction:         { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: t.tooltipBg,
                        titleColor:      t.tooltipTitle,
                        bodyColor:       t.tooltipBody,
                        borderColor:     'rgba(99,102,241,0.35)',
                        borderWidth:     1,
                        padding:         14,
                        cornerRadius:    12,
                        callbacks: {
                            label: c => ` ${c.dataset.label}: $${c.parsed.y !== null ? c.parsed.y.toFixed(2) : '—'}`,
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: {
                            maxTicksLimit: 8,
                            color: t.tickColor,
                            font: { size: 11 },
                            callback: function(v) {
                                const label = this.getLabelForValue(v);
                                const d = new Date(label);
                                return isNaN(d.getTime()) ? label : d.toLocaleString('en-AU', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false });
                            },
                        },
                        grid: { display: false },
                    },
                    y: {
                        ticks: {
                            color: t.tickColor,
                            font:  { size: 11 },
                            callback: v => `$${(+v).toFixed(2)}`,
                        },
                        grid: { color: t.gridColor },
                    },
                },
            },
        });

        oilToggleBtns = document.querySelectorAll('#oilToggles .oil-toggle');
        oilToggleBtns.forEach(btn => {
            applyOilToggleStyle(btn, btn.dataset.code === OIL_ACTIVE_CODE);

            btn.addEventListener('click', () => {
                if (OIL_ACTIVE_CODE === btn.dataset.code) return;
                OIL_ACTIVE_CODE = btn.dataset.code;
                const activeLabel = OIL_LABELS[OIL_ACTIVE_CODE] ?? OIL_ACTIVE_CODE;
                oilChartRef.data.datasets.forEach(d => { d.hidden = d.label !== activeLabel; });
                oilChartRef.update();
                oilToggleBtns.forEach(b => applyOilToggleStyle(b, b.dataset.code === OIL_ACTIVE_CODE));
            });
        });
    }

    initOilChart();

    new MutationObserver(() => {
        const t = getChartTheme();

        // Fuel trends chart
        chart.options.plugins.legend.labels.color             = t.legendColor;
        chart.options.plugins.tooltip.backgroundColor         = t.tooltipBg;
        chart.options.plugins.tooltip.titleColor              = t.tooltipTitle;
        chart.options.plugins.tooltip.bodyColor               = t.tooltipBody;
        chart.options.scales.x.ticks.color                   = t.tickColor;
        chart.options.scales.x.grid.color                    = t.gridColor;
        chart.options.scales.y.ticks.color                   = t.tickColor;
        chart.options.scales.y.grid.color                    = t.gridColor;
        chart.options.scales.y.title.color                   = t.yTitleColor;
        chart.update('none');

        // Brand share chart
        brandShareChart.data.datasets[0].borderColor                    = t.pieBorder;
        brandShareChart.options.plugins.tooltip.backgroundColor         = t.tooltipBg;
        brandShareChart.options.plugins.tooltip.titleColor              = t.tooltipTitle;
        brandShareChart.options.plugins.tooltip.bodyColor               = t.tooltipBody;
        brandShareChart.update('none');
        buildLegend(
            brandShareChart.data.labels,
            brandShareChart.data.datasets[0].data,
            shareInitial.counts
        );

        // Oil chart (may not be initialised yet)
        if (oilChartRef) {
            oilChartRef.options.scales.x.ticks.color             = t.tickColor;
            oilChartRef.options.scales.y.ticks.color             = t.tickColor;
            oilChartRef.options.scales.y.grid.color              = t.gridColor;
            oilChartRef.options.plugins.tooltip.backgroundColor  = t.tooltipBg;
            oilChartRef.options.plugins.tooltip.titleColor       = t.tooltipTitle;
            oilChartRef.options.plugins.tooltip.bodyColor        = t.tooltipBody;
            oilChartRef.update('none');
        }

        // Oil toggle buttons
        if (oilToggleBtns) {
            oilToggleBtns.forEach(b => applyOilToggleStyle(b, b.dataset.code === OIL_ACTIVE_CODE));
        }
    }).observe(document.documentElement, { attributeFilter: ['class'] });
</script>
@endscript
