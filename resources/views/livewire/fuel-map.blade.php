@assets
{{-- Google Maps bootstrap (loads once, deduped by Livewire @assets) --}}
<script>
(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await(a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})(
{key: "{{ config('services.google.maps_api_key') }}", v: "beta"});
</script>
<style>
/* ── Responsive map height ────────────────────────────── */
#fuelMapWrapper {
    /* Mobile: nav(56) + bottom-tabs(64) = 120px */
    height: calc(100dvh - 120px);
    min-height: 360px;
}
@media (min-width: 768px) {
    #fuelMapWrapper {
        /* Desktop: nav(56) + padding(24) + gap(12) ≈ 92px */
        height: calc(100vh - 92px);
        min-height: 520px;
    }
}

/* ── Google Places Autocomplete — light theme ─────────── */
.pac-container {
    background: rgba(255,255,255,0.97);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.12), 0 0 0 1px rgba(99,102,241,0.08);
    margin-top: 6px;
    padding: 4px;
    font-family: system-ui, -apple-system, sans-serif;
    overflow: hidden;
}
.pac-item {
    padding: 9px 12px;
    color: #475569;
    border-color: #f1f5f9;
    cursor: pointer;
    border-radius: 10px;
    margin: 1px 0;
    transition: background 0.12s;
    font-size: 13px;
}
.pac-item:hover { background: #eef2ff; }
.pac-item-selected { background: #eef2ff; }
.pac-item-query { color: #0f172a; font-size: 13px; font-weight: 600; }
.pac-matched { color: #6366f1; }
.pac-icon { display: none; }
.pac-logo { padding: 4px 12px 6px; }

/* ── Google Places Autocomplete — dark theme ──────────── */
html.dark .pac-container {
    background: rgba(15,23,42,0.97);
    border-color: #334155;
}
html.dark .pac-item {
    color: #94a3b8;
    border-color: #1e293b;
}
html.dark .pac-item:hover,
html.dark .pac-item-selected { background: #1e293b; }
html.dark .pac-item-query { color: #f1f5f9; }

/* ── Google Maps info window — strip default chrome ──────── */
.gm-style .gm-style-iw-c  { padding:0 !important; border-radius:16px !important; box-shadow:0 8px 32px rgba(0,0,0,0.14) !important; }
.gm-style .gm-style-iw-d  { overflow:hidden !important; }
.gm-style .gm-style-iw-chr { display:none !important; }
.gm-style .gm-style-iw-t::after { display:none !important; }

/* ── CSS hover for price pins (no JS listeners needed) ───── */
.fuel-pin {
    transition: transform .14s ease, box-shadow .14s ease;
}
.fuel-pin:hover {
    transform: var(--scale-h) !important;
    box-shadow: var(--shadow-h) !important;
}
</style>
@endassets

<div id="fuelMapWrapper" class="relative rounded-none sm:rounded-2xl overflow-hidden shadow-2xl">

    {{-- ── Top control bar ─────────────────────────────────── --}}
    <div class="absolute top-3 left-3 right-3 sm:top-4 sm:left-4 sm:right-4 z-10 flex flex-row items-center gap-2 sm:gap-2.5">

        {{-- Address search --}}
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none z-10">
                <svg class="w-4 h-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </div>
            <input id="addressSearch"
                   type="text"
                   placeholder="Search address…"
                   autocomplete="off"
                   class="w-full pl-10 pr-4 py-3 sm:py-2.5 text-sm text-slate-900 placeholder-slate-400
                          bg-white/90 backdrop-blur-xl
                          dark:bg-slate-900/90 dark:border-slate-700 dark:text-slate-100 dark:placeholder-slate-500
                          border border-slate-200 rounded-xl
                          focus:outline-none focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400/50
                          shadow-[0_4px_16px_rgba(0,0,0,0.08)] transition-all duration-200">
        </div>

        {{-- Fuel type select --}}
        <div class="relative">
            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none z-10">
                <svg class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h2l1 2h13l1-4H6M7 16a1 1 0 100 2 1 1 0 000-2zm10 0a1 1 0 100 2 1 1 0 000-2z"/>
                </svg>
            </div>
            <select wire:model.live="selectedFuelTypeId"
                    class="appearance-none pl-9 pr-8 py-3 sm:py-2.5 text-sm text-slate-900
                           bg-white/90 backdrop-blur-xl
                           dark:bg-slate-900/90 dark:border-slate-700 dark:text-slate-100
                           border border-slate-200 rounded-xl
                           focus:outline-none focus:ring-2 focus:ring-indigo-400/50
                           shadow-[0_4px_16px_rgba(0,0,0,0.08)] cursor-pointer
                           transition-all duration-200 w-[130px] sm:w-auto sm:min-w-[175px]">
                @foreach($fuelTypes as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </select>
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                <svg class="w-3 h-3 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
                </svg>
            </div>
        </div>

        {{-- Stats pills --}}
        <div id="mapStatsPills" class="hidden md:flex items-center gap-0 bg-white/90 backdrop-blur-xl border border-slate-200 dark:bg-slate-900/90 dark:border-slate-700 rounded-xl shadow-[0_4px_16px_rgba(0,0,0,0.08)] overflow-hidden" wire:loading.remove style="display:none!important">
            <div class="px-4 py-2.5">
                <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">Low</p>
                <p id="mapStatMin" class="text-sm font-bold text-emerald-600 leading-none">—</p>
            </div>
            <div class="w-px self-stretch bg-slate-200 dark:bg-slate-700"></div>
            <div class="px-4 py-2.5">
                <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-1">High</p>
                <p id="mapStatMax" class="text-sm font-bold text-red-500 leading-none">—</p>
            </div>
        </div>

        {{-- Loading state --}}
        <div wire:loading class="flex items-center gap-2 px-3.5 py-2.5 bg-white/90 backdrop-blur-xl border border-indigo-200 dark:bg-slate-900/90 dark:border-slate-700 rounded-xl shadow-[0_4px_16px_rgba(0,0,0,0.08)]">
            <svg class="animate-spin h-3.5 w-3.5 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            <span class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold whitespace-nowrap">Updating…</span>
        </div>

    </div>


    {{-- ── Zoom overlay ──────────────────────────────────────── --}}
    <div id="zoomOverlay" class="hidden absolute inset-0 z-20 flex items-center justify-center pointer-events-none">
        <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200 dark:bg-slate-900/95 dark:border-slate-700 p-8 text-center max-w-xs mx-4 pointer-events-auto">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM10.5 7.5v6m3-3h-6"/>
                </svg>
            </div>
            <h3 class="text-slate-900 dark:text-slate-100 font-bold text-base mb-1">Zoom in to see fuel stations</h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Zoom in to suburb level to load nearby fuel sites.</p>
            <button id="refocusMapBtn"
                    class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors duration-150">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                </svg>
                Reset Map
            </button>
        </div>
    </div>

    {{-- ── Map canvas ───────────────────────────────────────── --}}
    <div wire:ignore id="fuelMap" class="w-full h-full bg-slate-800"></div>

</div>

@script
<script>
    let TILE_SIZE = 0.5;  // degrees per tile edge
    let MIN_ZOOM  = 11;   // minimum map zoom to show markers

    // ── Session caches (keyed by fuelTypeId) ─────────────────
    let tileCache      = {};  // tileCache[fuelTypeId]['latTile_lngTile'] = Site[]
    let markerRegistry = {};  // markerRegistry[fuelTypeId][siteId] = AdvancedMarkerElement
    let statsCache     = {};  // statsCache[fuelTypeId] = { min, max, count, fuel_type_name }

    // ── Map state ─────────────────────────────────────────────
    let map, activeInfoWindow, activeMarker;
    let highlightedMin = null, highlightedMax = null;
    let currentFuelTypeName = '';
    let globalMin = 0, globalMax = 0;
    let currentFuelTypeId = parseInt($wire.selectedFuelTypeId);

    // ── Brand → favicon URL mapping ──────────────────────────
    let BRAND_LOGOS = {
        '7 Eleven':          'https://www.google.com/s2/favicons?domain=7eleven.com.au&sz=64',
        'Ampol':             'https://www.google.com/s2/favicons?domain=ampol.com.au&sz=64',
        'EG Ampol':          'https://www.google.com/s2/favicons?domain=ampol.com.au&sz=64',
        'Apco':              'https://www.google.com/s2/favicons?domain=apcostores.com.au&sz=64',
        'BP':                'https://companieslogo.com/img/orig/BP-6284f908.png?t=1770216700',
        'Caltex':            'https://www.google.com/s2/favicons?domain=caltex.com.au&sz=64',
        'Coles Express':     'https://www.google.com/s2/favicons?domain=colesexpress.com.au&sz=64',
        'Costco':            'https://www.google.com/s2/favicons?domain=costco.com.au&sz=64',
        'Freedom Fuels':     'https://www.google.com/s2/favicons?domain=freedomfuels.com.au&sz=64',
        'Gull':              'https://www.google.com/s2/favicons?domain=gull.com.au&sz=64',
        'Liberty':           'https://www.google.com/s2/favicons?domain=libertyoil.com.au&sz=64',
        'Mobil':             'https://www.google.com/s2/favicons?domain=mobil.com.au&sz=64',
        'On the Run':        'https://www.google.com/s2/favicons?domain=ontherun.com.au&sz=64',
        'Puma Energy':       'https://www.google.com/s2/favicons?domain=pumaenergy.com&sz=64',
        'Shell':             'https://www.google.com/s2/favicons?domain=shell.com.au&sz=64',
        'Reddy Express':     'https://www.google.com/s2/favicons?domain=shell.com.au&sz=64',
        'United':            'https://www.google.com/s2/favicons?domain=unitedpetroleum.com.au&sz=64',
        'Vibe':              'https://www.google.com/s2/favicons?domain=vibeenergy.com.au&sz=64',
        'Speedway':          'https://www.google.com/s2/favicons?domain=speedway.com.au&sz=64',
        'Metro Fuel':        'https://www.google.com/s2/favicons?domain=metropetroleum.com.au&sz=64',
        'Budget':            'https://www.google.com/s2/favicons?domain=budgetpetrol.com.au&sz=64',
        'Prime Petroleum':   'https://www.google.com/s2/favicons?domain=primepetroleum.com.au&sz=64',
        'U-Go':              'https://www.google.com/s2/favicons?domain=ugoselfserve.com.au&sz=64',
    };

    // ── Secondary fuel types shown in info window ─────────────
    let SECONDARY_FUELS = [
        { id: 2,  key: 'price_ul', label: 'Unleaded'     },
        { id: 5,  key: 'price_95', label: 'P.ULP 95'     },
        { id: 8,  key: 'price_98', label: 'P.ULP 98'     },
        { id: 14, key: 'price_pd', label: 'Prem. Diesel' },
    ];

    // ── Colour interpolation (green → yellow → red) ──────────
    function priceColor(price, min, max) {
        if (max <= min) return '#6366f1';
        const t = Math.max(0, Math.min(1, (price - min) / (max - min)));
        let r, g;
        if (t < 0.5) {
            const f = t * 2;
            r = Math.round(34 + f * 200);
            g = 197;
        } else {
            const f = (t - 0.5) * 2;
            r = 234;
            g = Math.round(197 - f * 129);
        }
        return `rgb(${r},${g},34)`;
    }

    // ── Logo circle element ───────────────────────────────────
    function makeLogoEl(brandName, color, size = 22) {
        const wrap = document.createElement('div');
        wrap.style.cssText = `
            width:${size}px;height:${size}px;border-radius:999px;
            background:#f8fafc;border:1.5px solid #e2e8f0;
            display:flex;align-items:center;justify-content:center;
            overflow:hidden;flex-shrink:0;
        `;
        const logoUrl = BRAND_LOGOS[brandName];
        if (logoUrl) {
            const img = document.createElement('img');
            img.src = logoUrl;
            const px = Math.round(size * 0.7);
            img.style.cssText = `width:${px}px;height:${px}px;object-fit:contain;`;
            img.onerror = () => {
                wrap.innerHTML = '';
                wrap.textContent = (brandName || '?').charAt(0).toUpperCase();
                wrap.style.background = color;
                wrap.style.border     = 'none';
                wrap.style.color      = '#fff';
                wrap.style.fontSize   = Math.round(size * 0.45) + 'px';
                wrap.style.fontWeight = '800';
                wrap.style.fontFamily = 'system-ui,sans-serif';
            };
            wrap.appendChild(img);
        } else {
            wrap.textContent = (brandName || '?').charAt(0).toUpperCase();
            wrap.style.background = color;
            wrap.style.border     = 'none';
            wrap.style.color      = '#fff';
            wrap.style.fontSize   = Math.round(size * 0.45) + 'px';
            wrap.style.fontWeight = '800';
            wrap.style.fontFamily = 'system-ui,sans-serif';
        }
        return wrap;
    }

    // ── Format price-update timestamp ────────────────────────
    function formatUpdated(dateStr) {
        if (!dateStr) return '—';
        const d      = new Date(dateStr + 'Z');
        const mins   = Math.floor((new Date() - d) / 60000);
        const hours  = Math.floor(mins / 60);
        const days   = Math.floor(hours / 24);
        const weeks  = Math.floor(days / 7);
        const months = Math.floor(days / 30);
        if (days > 28)   return months === 1 ? '1 month ago' : `${months} months ago`;
        if (days > 6)    return weeks  === 1 ? '1 week ago'  : `${weeks} weeks ago`;
        if (hours >= 48) return `${days} days ago`;
        if (hours > 0)   return mins % 60 > 0 ? `${hours}h ${mins % 60}m ago` : `${hours}h ago`;
        return `${mins}m ago`;
    }

    // ── Secondary price pill ──────────────────────────────────
    function pricePill(label, price, color) {
        const val  = price ? (price * 100).toFixed(1) : '—';
        const bold = price ? `color:${color};font-weight:700` : 'color:#cbd5e1;font-weight:500';
        return `<div style="flex:1;background:#f8fafc;border-radius:8px;padding:6px 8px;text-align:center">
                    <div style="font-size:9px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px">${label}</div>
                    <div style="font-size:12px;${bold}">${val}</div>
                </div>`;
    }

    // ── Custom price pin element ──────────────────────────────
    function makePinEl(price, min, max, brandName, highlight = null) {
        // No-price pin for sites without data for the selected fuel type
        if (price === null || price === undefined) {
            const el = document.createElement('div');
            el.style.cssText = `
                display:flex;align-items:center;
                background:#ffffff;
                padding:3px 8px 3px 3px;
                border-radius:10px;
                border:1px solid #e8edf2;
                border-left:3px solid #1e40af;
                box-shadow:0 1px 3px rgba(0,0,0,0.10),0 2px 8px rgba(0,0,0,0.06);
                cursor:pointer;
                white-space:nowrap;
            `;
            el.appendChild(makeLogoEl(brandName, '#1e40af', 22));
            el.classList.add('fuel-pin');
            el.style.setProperty('--scale-h', 'scale(1.10)');
            el.style.setProperty('--shadow-h', '0 3px 10px rgba(0,0,0,0.12)');
            return el;
        }

        const color    = priceColor(price, min, max);
        const pinColor = highlight ? color : '#1e40af';

        const hlBg     = highlight === 'cheapest' ? '#f0fdf4' : highlight === 'priciest' ? '#fef2f2' : null;
        const hlBorder = highlight === 'cheapest' ? '#86efac' : highlight === 'priciest' ? '#fca5a5' : null;
        const hlAccent = highlight === 'cheapest' ? '#16a34a' : highlight === 'priciest' ? '#dc2626' : null;
        const hlText   = highlight === 'cheapest' ? '#15803d' : highlight === 'priciest' ? '#b91c1c'  : null;
        const hlShadow = highlight === 'cheapest'
            ? '0 2px 10px rgba(22,163,74,0.20)'
            : highlight === 'priciest'
                ? '0 2px 10px rgba(220,38,38,0.20)'
                : null;

        const bg         = highlight ? hlBg     : '#ffffff';
        const border     = highlight ? `1px solid ${hlBorder}` : '1px solid #e8edf2';
        const borderLeft = highlight ? `3px solid ${hlAccent}` : `3px solid ${pinColor}`;
        const shadow     = highlight ? hlShadow : '0 1px 3px rgba(0,0,0,0.10),0 2px 8px rgba(0,0,0,0.06)';
        const baseScale  = highlight ? 'scale(1.14)' : 'scale(1)';

        const el = document.createElement('div');
        el.style.cssText = `
            display:flex;align-items:center;gap:5px;
            background:${bg};
            padding:${highlight ? '5px 10px 5px 5px' : '3px 8px 3px 3px'};
            border-radius:10px;
            border:${border};
            border-left:${borderLeft};
            box-shadow:${shadow};
            transform:${baseScale};
            cursor:pointer;
            transition:transform .14s ease,box-shadow .14s ease;
            white-space:nowrap;
        `;

        el.appendChild(makeLogoEl(brandName, color, highlight ? 26 : 22));

        const textWrap = document.createElement('div');
        textWrap.style.cssText = 'display:flex;flex-direction:column;gap:0px;';

        if (highlight) {
            const label = document.createElement('span');
            label.textContent = highlight === 'cheapest' ? '✓ CHEAPEST' : '▲ PRICIEST';
            label.style.cssText = `
                font-size:8px;font-weight:700;color:${hlText};
                font-family:system-ui,sans-serif;letter-spacing:0.07em;
                line-height:1;margin-bottom:1px;
            `;
            textWrap.appendChild(label);
        }

        const priceEl = document.createElement('span');
        priceEl.style.cssText = `
            font-size:${highlight ? '13' : '11'}px;font-weight:800;
            font-family:system-ui,sans-serif;
            color:${highlight ? hlText : pinColor};
            line-height:1;
        `;
        priceEl.textContent = (price * 100).toFixed(1);
        textWrap.appendChild(priceEl);
        el.appendChild(textWrap);

        el.classList.add('fuel-pin');
        el.style.setProperty('--scale-h', highlight ? 'scale(1.26)' : 'scale(1.12)');
        el.style.setProperty('--shadow-h', highlight
            ? hlShadow.replace('0.20', '0.35')
            : '0 3px 12px rgba(0,0,0,0.16),0 4px 16px rgba(0,0,0,0.10)');

        return el;
    }

    // ── Stats pills ───────────────────────────────────────────
    function updateStatsPills(stats) {
        const pills = document.getElementById('mapStatsPills');
        if (!pills) return;
        if (stats.count > 0) {
            document.getElementById('mapStatMin').textContent = (stats.min * 100).toFixed(1);
            document.getElementById('mapStatMax').textContent = (stats.max * 100).toFixed(1);
            pills.style.removeProperty('display');
        } else {
            pills.style.setProperty('display', 'none', 'important');
        }
    }

    // ── Map position persistence ──────────────────────────────
    const MAP_STORAGE_KEY = 'fuelmap_position';

    function saveMapPosition() {
        const c = map.getCenter();
        localStorage.setItem(MAP_STORAGE_KEY, JSON.stringify({
            lat:  c.lat(),
            lng:  c.lng(),
            zoom: map.getZoom(),
        }));
    }

    function loadMapPosition() {
        try {
            const raw = localStorage.getItem(MAP_STORAGE_KEY);
            if (raw) return JSON.parse(raw);
        } catch {}
        return null;
    }

    // ── Open info window ──────────────────────────────────────
    function openInfoWindow(m, site, min, max) {
        if (activeInfoWindow) activeInfoWindow.close();
        if (activeMarker) { activeMarker.zIndex = null; activeMarker = null; }
        m.zIndex     = 2000;
        activeMarker = m;

        const color    = site.price ? priceColor(site.price, min, max) : '#94a3b8';
        const logoUrl  = BRAND_LOGOS[site.brand];
        const logoHtml = logoUrl
            ? `<img src="${logoUrl}" style="width:36px;height:36px;flex-shrink:0;object-fit:contain;border-radius:8px;border:1px solid #e2e8f0;padding:3px;background:#fff;" onerror="this.style.display='none'">`
            : `<div style="width:36px;height:36px;flex-shrink:0;border-radius:8px;background:${color};display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;font-weight:800;font-family:system-ui,sans-serif;">${(site.brand||'?').charAt(0).toUpperCase()}</div>`;

        const fullAddr = [site.addr, `${site.suburb} QLD ${site.postcode}`]
            .filter(Boolean).join(', ');

        const priceSection = site.price
            ? `<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px">
                   <span style="font-size:9px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.08em;background:#eef2ff;padding:2px 8px;border-radius:6px">${currentFuelTypeName}</span>
                   <span style="font-size:10px;color:#94a3b8">Updated ${formatUpdated(site.updated)}</span>
               </div>
               <div style="display:flex;align-items:baseline;gap:3px">
                   <span style="font-size:36px;font-weight:900;color:${color};line-height:1;letter-spacing:-1px">${(site.price * 100).toFixed(1)}</span>
                   <span style="font-size:13px;color:#94a3b8;font-weight:600">/L</span>
               </div>`
            : `<div style="font-size:13px;color:#94a3b8;font-style:italic;padding:4px 0">No ${currentFuelTypeName} price available</div>`;

        const iw = new google.maps.InfoWindow({
            maxWidth: 290,
            content: `
            <div style="font-family:system-ui,-apple-system,sans-serif;width:280px;">
                <div style="display:flex;align-items:center;gap:11px;padding:14px 16px 11px;background:#fff;">
                    ${logoHtml}
                    <div style="min-width:0">
                        <div style="font-weight:700;font-size:13px;color:#0f172a;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${site.name}</div>
                        <div style="font-size:10px;color:#94a3b8;font-weight:500;margin-top:1px">${site.brand || ''}</div>
                    </div>
                </div>
                <div style="padding:0 16px 10px;background:#fff;">
                    <div style="font-size:10px;color:#64748b;line-height:1.6">${fullAddr}</div>
                </div>
                <div style="height:1px;background:#f1f5f9"></div>
                <div style="padding:12px 16px 10px;background:#fff;">
                    ${priceSection}
                </div>
                <div style="height:1px;background:#f1f5f9"></div>
                <div style="display:flex;gap:4px;padding:10px 12px;background:#fff;">
                    ${SECONDARY_FUELS
                        .filter(f => f.id !== parseInt($wire.selectedFuelTypeId))
                        .map(f => pricePill(f.label, site[f.key], priceColor(site[f.key] || 0, min, max)))
                        .join('')}
                </div>
            </div>`,
        });
        iw.open({ map, anchor: m });
        activeInfoWindow = iw;
    }

    // ── Highlight cheapest & priciest in viewport ─────────────
    function updateHighlights() {
        if (!map || !markerRegistry[currentFuelTypeId]) return;
        const bounds = map.getBounds();
        if (!bounds) return;

        let minMarker = null, maxMarker = null;
        let minPrice = Infinity, maxPrice = -Infinity;

        Object.values(markerRegistry[currentFuelTypeId]).forEach(m => {
            if (!m.map) return;
            if (!m._site.price) return; // skip sites without price for this fuel type
            if (bounds.contains(m.position)) {
                if (m._site.price < minPrice) { minPrice = m._site.price; minMarker = m; }
                if (m._site.price > maxPrice) { maxPrice = m._site.price; maxMarker = m; }
            }
        });

        if (minMarker === maxMarker) { minMarker = null; maxMarker = null; }

        if (highlightedMin !== minMarker) {
            if (highlightedMin) {
                const s = highlightedMin._site;
                highlightedMin.content = makePinEl(s.price, globalMin, globalMax, s.brand);
                highlightedMin.zIndex  = null;
            }
            highlightedMin = minMarker;
            if (minMarker) {
                const s = minMarker._site;
                minMarker.content = makePinEl(s.price, globalMin, globalMax, s.brand, 'cheapest');
                minMarker.zIndex  = 1000;
            }
        }

        if (highlightedMax !== maxMarker) {
            if (highlightedMax) {
                const s = highlightedMax._site;
                highlightedMax.content = makePinEl(s.price, globalMin, globalMax, s.brand);
                highlightedMax.zIndex  = null;
            }
            highlightedMax = maxMarker;
            if (maxMarker) {
                const s = maxMarker._site;
                maxMarker.content = makePinEl(s.price, globalMin, globalMax, s.brand, 'priciest');
                maxMarker.zIndex  = 999;
            }
        }
    }

    // ── Zoom overlay helpers ──────────────────────────────────
    function showZoomOverlay() {
        document.getElementById('zoomOverlay').classList.remove('hidden');
    }
    function hideZoomOverlay() {
        document.getElementById('zoomOverlay').classList.add('hidden');
    }
    function refocusMap() {
        map.setCenter({ lat: -27.4698, lng: 153.0251 });
        map.setZoom(12);
    }
    document.getElementById('refocusMapBtn').addEventListener('click', refocusMap);

    // ── Tile coordinate helpers ───────────────────────────────
    function tileKey(latTile, lngTile) {
        return `${latTile}_${lngTile}`;
    }

    function getTilesForBounds(bounds) {
        const sw = bounds.getSouthWest(), ne = bounds.getNorthEast();
        const tiles = [];
        for (let lat = Math.floor(sw.lat() / TILE_SIZE); lat <= Math.floor(ne.lat() / TILE_SIZE); lat++) {
            for (let lng = Math.floor(sw.lng() / TILE_SIZE); lng <= Math.floor(ne.lng() / TILE_SIZE); lng++) {
                tiles.push({ lat, lng });
            }
        }
        return tiles;
    }

    // ── Fetch helpers ─────────────────────────────────────────
    async function fetchTile(fuelTypeId, latTile, lngTile) {
        const res = await fetch(`/map-tiles/${fuelTypeId}/${latTile}/${lngTile}`);
        return res.json();
    }

    async function fetchStats(fuelTypeId) {
        if (statsCache[fuelTypeId]) return statsCache[fuelTypeId];
        const res  = await fetch(`/map-stats/${fuelTypeId}`);
        const data = await res.json();
        statsCache[fuelTypeId] = data;
        return data;
    }

    // ── Hide all markers for a fuel type ─────────────────────
    function hideAllMarkersForFuelType(fuelTypeId) {
        const registry = markerRegistry[fuelTypeId];
        if (!registry) return;
        Object.values(registry).forEach(m => { m.map = null; });
    }

    // ── Load tiles covering the current viewport ──────────────
    async function loadViewportTiles(fuelTypeId, bounds) {
        if (!markerRegistry[fuelTypeId]) markerRegistry[fuelTypeId] = {};
        if (!tileCache[fuelTypeId])      tileCache[fuelTypeId]      = {};

        const viewportTiles = getTilesForBounds(bounds);
        const viewportKeys  = new Set(viewportTiles.map(t => tileKey(t.lat, t.lng)));

        // Show cached tile markers immediately
        viewportTiles.forEach(t => {
            const key   = tileKey(t.lat, t.lng);
            const sites = tileCache[fuelTypeId][key];
            if (!sites) return;
            sites.forEach(site => {
                const m = markerRegistry[fuelTypeId][site.id];
                if (m) m.map = map;
            });
        });

        // Hide markers whose tile has left the viewport
        Object.values(markerRegistry[fuelTypeId]).forEach(m => {
            if (m._tileKey && !viewportKeys.has(m._tileKey)) m.map = null;
        });

        // Fetch missing tiles in parallel
        const missingTiles = viewportTiles.filter(
            t => tileCache[fuelTypeId][tileKey(t.lat, t.lng)] === undefined
        );

        if (missingTiles.length === 0) {
            updateHighlights();
            return;
        }

        await Promise.all(missingTiles.map(async t => {
            const key  = tileKey(t.lat, t.lng);
            const data = await fetchTile(fuelTypeId, t.lat, t.lng);

            tileCache[fuelTypeId][key] = data.sites ?? [];

            // Only render markers if this tile is still in the viewport that triggered the fetch
            if (!viewportKeys.has(key)) return;

            (data.sites ?? []).forEach(site => {
                if (markerRegistry[fuelTypeId][site.id]) {
                    markerRegistry[fuelTypeId][site.id].map = map;
                    return;
                }
                const m = new google.maps.marker.AdvancedMarkerElement({
                    position: { lat: site.lat, lng: site.lng },
                    map,
                    title:   site.price ? `${site.name} — ${(site.price * 100).toFixed(1)}/L` : site.name,
                    content: makePinEl(site.price, globalMin, globalMax, site.brand),
                });
                m._site    = site;
                m._tileKey = key;
                m.addListener('click', () => openInfoWindow(m, site, globalMin, globalMax));
                markerRegistry[fuelTypeId][site.id] = m;
            });
        }));

        updateHighlights();
    }

    // ── Initialise Google Map ─────────────────────────────────
    async function initMap() {
        const { Map }          = await google.maps.importLibrary('maps');
        await google.maps.importLibrary('marker');
        const { Autocomplete } = await google.maps.importLibrary('places');

        const saved = loadMapPosition();

        map = new Map(document.getElementById('fuelMap'), {
            center:            saved ? { lat: saved.lat, lng: saved.lng } : { lat: -27.4698, lng: 153.0251 },
            zoom:              saved ? saved.zoom : 12,
            mapId:             'DEMO_MAP_ID',
            clickableIcons:    false,
            mapTypeControl:    false,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControlOptions: { position: google.maps.ControlPosition.RIGHT_CENTER },
        });

        let highlightTimer = null;

        map.addListener('idle', async () => {
            saveMapPosition();

            const zoom = map.getZoom();
            if (zoom < MIN_ZOOM) {
                showZoomOverlay();
                hideAllMarkersForFuelType(currentFuelTypeId);
                return;
            }
            hideZoomOverlay();

            if (!statsCache[currentFuelTypeId]) {
                const stats = await fetchStats(currentFuelTypeId);
                globalMin           = stats.min;
                globalMax           = stats.max;
                currentFuelTypeName = stats.fuel_type_name;
                updateStatsPills(stats);
            }

            await loadViewportTiles(currentFuelTypeId, map.getBounds());

            clearTimeout(highlightTimer);
            highlightTimer = setTimeout(updateHighlights, 150);
        });

        map.addListener('click', () => {
            if (activeInfoWindow) { activeInfoWindow.close(); activeInfoWindow = null; }
            if (activeMarker) { activeMarker.zIndex = null; activeMarker = null; }
        });

        // ── Address search ────────────────────────────────────
        const input = document.getElementById('addressSearch');
        const ac    = new Autocomplete(input, {
            componentRestrictions: { country: 'au' },
            fields: ['geometry', 'name'],
        });
        ac.addListener('place_changed', () => {
            const place = ac.getPlace();
            if (place.geometry?.location) {
                map.panTo(place.geometry.location);
                map.setZoom(15);
            }
        });
    }

    initMap();

    // ── Persist selected fuel type across visits ──────────────
    let FUEL_TYPE_KEY = 'fuelmap_fuel_type';
    let savedFuelType = localStorage.getItem(FUEL_TYPE_KEY);
    if (savedFuelType && parseInt(savedFuelType) !== currentFuelTypeId) {
        $wire.set('selectedFuelTypeId', parseInt(savedFuelType));
    }

    // ── Livewire event — fuel type changed ───────────────────
    $wire.on('fuelTypeChanged', async ({ fuelTypeId }) => {
        localStorage.setItem(FUEL_TYPE_KEY, fuelTypeId);

        if (activeInfoWindow) { activeInfoWindow.close(); activeInfoWindow = null; }
        if (activeMarker)     { activeMarker.zIndex = null; activeMarker = null; }

        if (highlightedMin) {
            const s = highlightedMin._site;
            highlightedMin.content = makePinEl(s.price, globalMin, globalMax, s.brand);
            highlightedMin.zIndex  = null;
            highlightedMin         = null;
        }
        if (highlightedMax) {
            const s = highlightedMax._site;
            highlightedMax.content = makePinEl(s.price, globalMin, globalMax, s.brand);
            highlightedMax.zIndex  = null;
            highlightedMax         = null;
        }

        hideAllMarkersForFuelType(currentFuelTypeId);
        currentFuelTypeId = parseInt(fuelTypeId);

        const stats = await fetchStats(currentFuelTypeId);
        globalMin           = stats.min;
        globalMax           = stats.max;
        currentFuelTypeName = stats.fuel_type_name;
        updateStatsPills(stats);

        if (map && map.getZoom() >= MIN_ZOOM) {
            await loadViewportTiles(currentFuelTypeId, map.getBounds());
        }
    });
</script>
@endscript
