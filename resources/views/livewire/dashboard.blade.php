@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@endassets

<div>
    {{-- Filter bar --}}
    <div class="mb-6 bg-white rounded-lg shadow p-4 flex flex-wrap gap-6 items-end">

        {{-- Date range --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">From</label>
            <input type="date" wire:model="dateFrom"
                   class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">To</label>
            <input type="date" wire:model="dateTo"
                   class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        </div>

        {{-- Fuel type multi-select --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-2">Fuel Types</label>
            <div class="flex flex-wrap gap-x-4 gap-y-2">
                @foreach($fuelTypes as $type)
                    <label class="flex items-center gap-1.5 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox"
                               wire:model="selectedFuelTypes"
                               value="{{ $type->id }}"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-400">
                        {{ $type->name }}
                    </label>
                @endforeach
            </div>
        </div>

        <button wire:click="applyFilters"
                class="bg-gray-800 text-white rounded-md px-4 py-2 text-sm font-medium hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 self-end">
            Apply Filters
        </button>
        <div wire:loading class="text-sm text-gray-400 self-end pb-2">Updating...</div>
    </div>

    {{-- Chart --}}
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">Average Fuel Price by Type Over Time</h2>
        <div wire:ignore>
            <canvas id="chartFuelTrends"></canvas>
        </div>
        @if(empty($chartData['datasets']))
            <p class="text-center text-sm text-gray-400 mt-4">Select at least one fuel type to display the chart.</p>
        @endif
    </div>
</div>

@script
<script>
    const PALETTE = [
        { border: 'rgb(59,130,246)',  bg: 'rgba(59,130,246,0.08)'  },
        { border: 'rgb(239,68,68)',   bg: 'rgba(239,68,68,0.08)'   },
        { border: 'rgb(34,197,94)',   bg: 'rgba(34,197,94,0.08)'   },
        { border: 'rgb(245,158,11)',  bg: 'rgba(245,158,11,0.08)'  },
        { border: 'rgb(168,85,247)',  bg: 'rgba(168,85,247,0.08)'  },
        { border: 'rgb(20,184,166)',  bg: 'rgba(20,184,166,0.08)'  },
        { border: 'rgb(249,115,22)',  bg: 'rgba(249,115,22,0.08)'  },
        { border: 'rgb(236,72,153)', bg: 'rgba(236,72,153,0.08)'  },
    ];

    function buildDatasets(datasets) {
        return datasets.map((ds, i) => {
            const color = PALETTE[i % PALETTE.length];
            return {
                label: ds.label,
                data: ds.data,
                borderColor: color.border,
                backgroundColor: color.bg,
                borderWidth: 2,
                tension: 0.3,
                fill: false,
                pointRadius: 3,
                pointHoverRadius: 5,
                spanGaps: false,
            };
        });
    }

    const initial = @json($chartData);

    const chart = new Chart(document.getElementById('chartFuelTrends'), {
        type: 'line',
        data: {
            labels: initial.labels,
            datasets: buildDatasets(initial.datasets),
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y !== null ? ctx.parsed.y + ' ¢/L' : 'N/A'}`,
                    },
                },
            },
            scales: {
                x: { ticks: { maxTicksLimit: 10, font: { size: 11 } } },
                y: {
                    ticks: { font: { size: 11 }, callback: v => v + ' ¢' },
                    title: { display: true, text: 'Avg Price (¢/L)', font: { size: 11 } },
                },
            },
        },
    });

    $wire.on('chartUpdated', ({ labels, datasets }) => {
        chart.data.labels = labels;
        chart.data.datasets = buildDatasets(datasets);
        chart.update();
    });
</script>
@endscript
