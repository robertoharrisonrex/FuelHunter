<div class="dash-card bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <div class="p-6 pb-4 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 border-b border-slate-100">
        <div>
            <h2 class="text-slate-900 text-xl font-bold tracking-tight">Regional Price Heat Map</h2>
            <p class="text-slate-500 text-sm mt-0.5">Average price vs QLD state average · current prices</p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Fuel Type</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach($fuelTypes as $type)
                    <button wire:click="selectFuelType({{ $type->id }})"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-all duration-150
                                   {{ $selectedFuelTypeId === $type->id
                                       ? 'bg-indigo-600 text-white border-indigo-600'
                                       : 'border-gray-200 bg-gray-50 text-gray-500 hover:border-indigo-300 hover:text-indigo-500 hover:bg-indigo-50' }}">
                        {{ $type->name }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Map ─────────────────────────────────────────────────── --}}
    <div wire:ignore class="relative h-[480px]">
        <div id="regionalHeatmap" class="w-full h-full"></div>
    </div>

    {{-- ── Footer ──────────────────────────────────────────────── --}}
    <div class="px-6 py-3 border-t border-slate-100 flex items-center justify-between gap-4">
        <button id="heatmapBackBtn"
                onclick="heatmapBackToCities()"
                class="hidden items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">
            ← Back to cities
        </button>
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <span>Cheaper</span>
            <div class="w-24 h-2 rounded-full"
                 style="background: linear-gradient(to right, #22c55e, #f59e0b, #ef4444)"></div>
            <span>Dearer</span>
            <span class="text-slate-300 ml-1">vs QLD avg</span>
        </div>
        <span id="heatmapSubtitle" class="text-xs text-slate-400">Click a circle to see suburbs</span>
    </div>

</div>
