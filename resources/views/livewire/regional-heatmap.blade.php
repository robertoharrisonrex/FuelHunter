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

@script
<script>
let heatmapMap     = null;
let heatmapMarkers = [];
let activeInfoWin  = null;
let activeCityId   = null;
let currentFuelId  = $wire.selectedFuelTypeId;
let heatmapAbortCtrl = null;

function heatmapEscapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function heatmapClearMarkers() {
    heatmapMarkers.forEach(m => m.setMap(null));
    heatmapMarkers = [];
    if (activeInfoWin) { activeInfoWin.close(); activeInfoWin = null; }
}

function heatmapInterpolateColour(t) {
    const stops = [
        [34,  197, 94],
        [245, 158, 11],
        [239, 68,  68],
    ];
    const scaled = t * (stops.length - 1);
    const lo     = Math.floor(scaled);
    const hi     = Math.min(lo + 1, stops.length - 1);
    const frac   = scaled - lo;
    const r = Math.round(stops[lo][0] + frac * (stops[hi][0] - stops[lo][0]));
    const g = Math.round(stops[lo][1] + frac * (stops[hi][1] - stops[lo][1]));
    const b = Math.round(stops[lo][2] + frac * (stops[hi][2] - stops[lo][2]));
    return `#${r.toString(16).padStart(2,'0')}${g.toString(16).padStart(2,'0')}${b.toString(16).padStart(2,'0')}`;
}

function heatmapRadius(siteCount) {
    const MIN_SITES = 10, MAX_SITES = 200;
    const MIN_R = 8000,   MAX_R = 25000;
    const t = Math.min(1, Math.max(0, (siteCount - MIN_SITES) / (MAX_SITES - MIN_SITES)));
    return MIN_R + t * (MAX_R - MIN_R);
}

function heatmapCreateCircle(item, deviations, isSuburb) {
    const minDev  = Math.min(...deviations);
    const maxDev  = Math.max(...deviations);
    const range   = maxDev - minDev || 1;
    const t       = (item.deviation - minDev) / range;
    const colour  = heatmapInterpolateColour(t);
    const priceC  = (item.avg_price / 100).toFixed(1);
    const devC    = Math.abs(item.deviation / 100).toFixed(1);
    const devLabel = item.deviation >= 0
        ? `<span style="color:#ef4444;font-weight:700">+${devC} ¢ dearer</span>`
        : `<span style="color:#22c55e;font-weight:700">−${devC} ¢ cheaper</span>`;
    const name    = isSuburb ? item.suburb_name : item.city_name;
    const id      = isSuburb ? item.suburb_id   : item.city_id;
    const vsLabel = isSuburb ? 'vs city avg' : 'vs QLD avg';

    const circle = new google.maps.Circle({
        map:          heatmapMap,
        center:       { lat: item.lat, lng: item.lng },
        radius:       heatmapRadius(item.site_count),
        fillColor:    colour,
        fillOpacity:  0.72,
        strokeColor:  '#ffffff',
        strokeWeight: 2,
        clickable:    true,
    });

    const infoContent = `
        <div style="font-family:system-ui,sans-serif;padding:14px 16px;min-width:180px">
            <p style="font-weight:700;font-size:14px;color:#0f172a;margin:0 0 8px">${heatmapEscapeHtml(name)}</p>
            <p style="font-size:12px;color:#475569;margin:0 0 3px">Avg price: <b>${priceC} ¢/L</b></p>
            <p style="font-size:12px;color:#475569;margin:0 0 3px">${vsLabel}: ${devLabel}</p>
            <p style="font-size:12px;color:#475569;margin:0 0 ${isSuburb ? 0 : 10}px">Sites: <b>${item.site_count}</b></p>
            ${!isSuburb ? `<a href="#" data-city-id="${id}" data-city-name="${heatmapEscapeHtml(name)}"
               style="font-size:12px;color:#4f46e5;font-weight:600;text-decoration:none"
               class="heatmap-drill">See suburbs →</a>` : ''}
        </div>`;

    const infoWin = new google.maps.InfoWindow({ content: infoContent });

    circle.addListener('click', () => {
        if (activeInfoWin) activeInfoWin.close();
        infoWin.setPosition({ lat: item.lat, lng: item.lng });
        infoWin.open(heatmapMap);
        activeInfoWin = infoWin;

        if (!isSuburb) {
            google.maps.event.addListenerOnce(infoWin, 'domready', () => {
                document.querySelectorAll('.heatmap-drill').forEach(el => {
                    el.addEventListener('click', e => {
                        e.preventDefault();
                        const cityId   = parseInt(el.dataset.cityId);
                        const cityName = el.dataset.cityName;
                        infoWin.close();
                        heatmapDrillDown(cityId, cityName);
                    });
                });
            });
        }
    });

    heatmapMarkers.push(circle);
}

async function heatmapFetchCities(fuelId) {
    if (heatmapAbortCtrl) heatmapAbortCtrl.abort();
    heatmapAbortCtrl = new AbortController();
    try {
        const res  = await fetch(`/map-heatmap/${fuelId}`, { signal: heatmapAbortCtrl.signal });
        if (!res.ok) return;
        const data = await res.json();
        heatmapClearMarkers();
        activeCityId = null;
        document.getElementById('heatmapBackBtn').classList.add('hidden');
        document.getElementById('heatmapBackBtn').style.display = '';
        document.getElementById('heatmapSubtitle').textContent = 'Click a circle to see suburbs';
        if (!data.length) return;
        const devs = data.map(d => d.deviation);
        data.forEach(item => heatmapCreateCircle(item, devs, false));
    } catch (e) {
        if (e.name !== 'AbortError') throw e;
    }
}

async function heatmapDrillDown(cityId, cityName) {
    if (heatmapAbortCtrl) heatmapAbortCtrl.abort();
    heatmapAbortCtrl = new AbortController();
    try {
        const res  = await fetch(`/map-heatmap/${currentFuelId}/city/${cityId}`, { signal: heatmapAbortCtrl.signal });
        if (!res.ok) return;
        const data = await res.json();
        heatmapClearMarkers();
        activeCityId = cityId;
        if (!data.length) {
            document.getElementById('heatmapBackBtn').style.display = 'flex';
            document.getElementById('heatmapBackBtn').classList.remove('hidden');
            document.getElementById('heatmapSubtitle').textContent = `${heatmapEscapeHtml(cityName)} — no suburb data`;
            return;
        }
        document.getElementById('heatmapBackBtn').style.display = 'flex';
        document.getElementById('heatmapBackBtn').classList.remove('hidden');
        document.getElementById('heatmapSubtitle').textContent = heatmapEscapeHtml(cityName);
        const devs = data.map(d => d.deviation);
        data.forEach(item => heatmapCreateCircle(item, devs, true));
        const bounds = new google.maps.LatLngBounds();
        data.forEach(d => bounds.extend({ lat: d.lat, lng: d.lng }));
        heatmapMap.fitBounds(bounds);
    } catch (e) {
        if (e.name !== 'AbortError') throw e;
    }
}

window.heatmapBackToCities = function () {
    activeCityId = null;
    document.getElementById('heatmapBackBtn').style.display = '';
    document.getElementById('heatmapBackBtn').classList.add('hidden');
    heatmapMap.setCenter({ lat: -22.0, lng: 144.0 });
    heatmapMap.setZoom(5);
    heatmapFetchCities(currentFuelId);
};

(async () => {
    await google.maps.importLibrary('maps');
    heatmapMap = new google.maps.Map(document.getElementById('regionalHeatmap'), {
        center:            { lat: -22.0, lng: 144.0 },
        zoom:              5,
        mapTypeId:         'roadmap',
        disableDefaultUI:  false,
        zoomControl:       true,
        streetViewControl: false,
        mapTypeControl:    false,
    });
    heatmapFetchCities(currentFuelId);
})();

$wire.on('heatmapFuelChanged', ({ fuelTypeId }) => {
    currentFuelId = fuelTypeId;
    heatmapFetchCities(fuelTypeId);
});
</script>
@endscript
