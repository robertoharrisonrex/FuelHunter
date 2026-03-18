@assets
{{-- Google Maps bootstrap (loads once, deduped by Livewire @assets) --}}
<script>
(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await(a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})(
{key: "{{ env('GOOGLE_API_TOKEN') }}", v: "beta"});
</script>
<style>
/* ── Responsive map height ────────────────────────────── */
#fuelMapWrapper {
    height: calc(100dvh - 155px);
    min-height: 400px;
}
@media (min-width: 640px) {
    #fuelMapWrapper {
        height: calc(100vh - 220px);
        min-height: 520px;
    }
}

/* ── Google Places Autocomplete — dark theme ──────────── */
.pac-container {
    background: rgba(10,15,30,0.97);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    box-shadow: 0 28px 56px rgba(0,0,0,0.55), 0 0 0 1px rgba(99,102,241,0.08);
    margin-top: 6px;
    padding: 4px;
    font-family: system-ui, -apple-system, sans-serif;
    overflow: hidden;
}
.pac-item {
    padding: 9px 12px;
    color: #94a3b8;
    border-color: rgba(255,255,255,0.04);
    cursor: pointer;
    border-radius: 10px;
    margin: 1px 0;
    transition: background 0.12s;
    font-size: 13px;
}
.pac-item:hover { background: rgba(99,102,241,0.18); }
.pac-item-selected { background: rgba(99,102,241,0.22); }
.pac-item-query { color: #f1f5f9; font-size: 13px; font-weight: 600; }
.pac-matched { color: #818cf8; }
.pac-icon { display: none; }
.pac-logo { padding: 4px 12px 6px; }
</style>
@endassets

<div id="fuelMapWrapper" class="relative rounded-none sm:rounded-2xl overflow-hidden shadow-2xl">

    {{-- ── Top control bar ─────────────────────────────────── --}}
    <div class="absolute top-3 left-3 right-3 sm:top-4 sm:left-4 sm:right-4 z-10 flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-2.5">

        {{-- Address search --}}
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none z-10">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </div>
            <input id="addressSearch"
                   type="text"
                   placeholder="Search suburb or address…"
                   autocomplete="off"
                   class="w-full pl-10 pr-4 py-3 sm:py-2.5 text-sm text-slate-100 placeholder-slate-500
                          bg-slate-950/90 backdrop-blur-xl
                          border border-white/[0.07] rounded-xl
                          focus:outline-none focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-500/40
                          shadow-[0_8px_32px_rgba(0,0,0,0.45)] transition-all duration-200">
        </div>

        {{-- Fuel type select --}}
        <div class="relative">
            <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none z-10">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h2l1 2h13l1-4H6M7 16a1 1 0 100 2 1 1 0 000-2zm10 0a1 1 0 100 2 1 1 0 000-2z"/>
                </svg>
            </div>
            <select wire:model.live="selectedFuelTypeId"
                    class="appearance-none pl-9 pr-8 py-3 sm:py-2.5 text-sm text-slate-100
                           bg-slate-950/90 backdrop-blur-xl w-full
                           border border-white/[0.07] rounded-xl
                           focus:outline-none focus:ring-2 focus:ring-indigo-500/40
                           shadow-[0_8px_32px_rgba(0,0,0,0.45)] cursor-pointer
                           transition-all duration-200 sm:min-w-[175px]">
                @foreach($fuelTypes as $type)
                    <option value="{{ $type->id }}" style="background:#020617;color:#f1f5f9">{{ $type->name }}</option>
                @endforeach
            </select>
            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                <svg class="w-3 h-3 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/>
                </svg>
            </div>
        </div>

        {{-- Stats pills --}}
        @if($mapData['count'] > 0)
        <div class="hidden md:flex items-center gap-0 bg-slate-950/90 backdrop-blur-xl border border-white/[0.07] rounded-xl shadow-[0_8px_32px_rgba(0,0,0,0.45)] overflow-hidden" wire:loading.remove>
            <div class="px-4 py-2.5">
                <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest leading-none mb-1">Stations</p>
                <p class="text-sm font-bold text-white leading-none">{{ $mapData['count'] }}</p>
            </div>
            <div class="w-px self-stretch bg-white/[0.06]"></div>
            <div class="px-4 py-2.5">
                <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest leading-none mb-1">Low</p>
                <p class="text-sm font-bold text-emerald-400 leading-none">${{ number_format($mapData['min'], 2) }}</p>
            </div>
            <div class="w-px self-stretch bg-white/[0.06]"></div>
            <div class="px-4 py-2.5">
                <p class="text-[9px] font-bold text-slate-600 uppercase tracking-widest leading-none mb-1">High</p>
                <p class="text-sm font-bold text-red-400 leading-none">${{ number_format($mapData['max'], 2) }}</p>
            </div>
        </div>
        @endif

        {{-- Loading state --}}
        <div wire:loading class="flex items-center gap-2 px-3.5 py-2.5 bg-slate-950/90 backdrop-blur-xl border border-indigo-500/25 rounded-xl shadow-[0_8px_32px_rgba(0,0,0,0.45)]">
            <svg class="animate-spin h-3.5 w-3.5 text-indigo-400 flex-shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
            <span class="text-xs text-indigo-300 font-semibold whitespace-nowrap">Updating…</span>
        </div>

    </div>

    {{-- ── Price legend (bottom-left) ──────────────────────── --}}
    <div class="absolute bottom-8 left-4 z-10 px-4 py-3 bg-slate-950/90 backdrop-blur-xl border border-white/[0.07] rounded-xl shadow-[0_8px_32px_rgba(0,0,0,0.45)]">
        <p class="text-[9px] font-bold text-slate-500 uppercase tracking-widest mb-2">Price</p>
        <div class="h-1.5 w-32 rounded-full" style="background: linear-gradient(to right, #22c55e, #eab308, #ef4444)"></div>
        <div class="flex justify-between text-[9px] text-slate-500 mt-1">
            <span>Cheapest</span>
            <span>Most expensive</span>
        </div>
    </div>

    {{-- ── Map canvas ───────────────────────────────────────── --}}
    <div wire:ignore id="fuelMap" class="w-full h-full bg-slate-800"></div>

</div>

@script
<script>
    const initial   = @json($mapData);
    let map, activeInfoWindow;
    let markers = [];
    let currentFuelTypeName = initial.fuel_type_name ?? '';
    let highlightedMin = null, highlightedMax = null;

    // ── Brand → favicon URL mapping ──────────────────────────
    const BRAND_LOGOS = {
        '7 Eleven':          'https://www.google.com/s2/favicons?domain=7eleven.com.au&sz=64',
        'Ampol':             'https://www.google.com/s2/favicons?domain=ampol.com.au&sz=64',
        'EG Ampol':          'https://www.google.com/s2/favicons?domain=ampol.com.au&sz=64',
        'Apco':              'https://www.google.com/s2/favicons?domain=apcostores.com.au&sz=64',
        'BP':                'https://www.google.com/s2/favicons?domain=bp.com&sz=64',
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
        'United':            'https://www.google.com/s2/favicons?domain=unitedpetroleum.com.au&sz=64',
        'Vibe':              'https://www.google.com/s2/favicons?domain=vibeenergy.com.au&sz=64',
        'Speedway':          'https://www.google.com/s2/favicons?domain=speedway.com.au&sz=64',
        'Metro Fuel':        'https://www.google.com/s2/favicons?domain=metrofuel.com.au&sz=64',
        'Budget':            'https://www.google.com/s2/favicons?domain=budgetpetrol.com.au&sz=64',
        'Prime Petroleum':   'https://www.google.com/s2/favicons?domain=primepetroleum.com.au&sz=64',
    };

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

    // ── Logo circle element (img with initial fallback) ──────
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
        const d    = new Date(dateStr + 'Z');
        const days = Math.floor((new Date() - d) / 86400000);
        if (days === 0) return 'Today';
        if (days === 1) return 'Yesterday';
        if (days < 7)  return `${days} days ago`;
        return d.toLocaleDateString('en-AU', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    // ── Small secondary-price cell for info window ────────────
    function pricePill(label, price, color) {
        const val   = price ? '$' + price.toFixed(2) : '—';
        const bold  = price ? `color:${color};font-weight:700` : 'color:#cbd5e1;font-weight:500';
        return `<div style="flex:1;text-align:center">
                    <div style="font-size:9px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px">${label}</div>
                    <div style="font-size:12px;${bold}">${val}</div>
                </div>`;
    }

    // ── Custom price pin element ─────────────────────────────
    // highlight: null | 'cheapest' | 'priciest'
    function makePinEl(price, min, max, brandName, highlight = null) {
        const color   = priceColor(price, min, max);
        const hlColor = highlight === 'cheapest' ? '#16a34a' : highlight === 'priciest' ? '#dc2626' : null;
        const baseScale = highlight ? 'scale(1.18)' : 'scale(1)';

        const el = document.createElement('div');
        el.style.cssText = `
            display:flex;align-items:center;gap:5px;
            background:${highlight ? hlColor : '#fff'};
            padding:${highlight ? '5px 10px 5px 5px' : '3px 8px 3px 3px'};
            border-radius:999px;
            box-shadow:${highlight
                ? `0 4px 16px rgba(0,0,0,0.4),0 0 0 3px ${hlColor},0 0 18px ${hlColor}66`
                : `0 2px 10px rgba(0,0,0,0.28),0 0 0 1.5px ${color}44`};
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
            label.textContent = highlight === 'cheapest' ? '★ CHEAPEST' : '▲ PRICIEST';
            label.style.cssText = `
                font-size:8px;font-weight:900;color:rgba(255,255,255,0.85);
                font-family:system-ui,sans-serif;letter-spacing:0.07em;
                line-height:1;margin-bottom:1px;
            `;
            textWrap.appendChild(label);
        }

        const priceEl = document.createElement('span');
        priceEl.style.cssText = `
            font-size:${highlight ? '13' : '11'}px;font-weight:800;
            font-family:system-ui,sans-serif;
            color:${highlight ? '#fff' : color};
            line-height:1;
        `;
        priceEl.textContent = '$' + price.toFixed(2);
        textWrap.appendChild(priceEl);
        el.appendChild(textWrap);

        el.addEventListener('mouseenter', () => {
            el.style.transform = highlight ? 'scale(1.30)' : 'scale(1.18)';
            el.style.boxShadow = highlight
                ? `0 6px 22px rgba(0,0,0,0.45),0 0 0 3px ${hlColor},0 0 28px ${hlColor}88`
                : `0 5px 18px rgba(0,0,0,0.35),0 0 0 2px ${color}77`;
        });
        el.addEventListener('mouseleave', () => {
            el.style.transform = baseScale;
            el.style.boxShadow = highlight
                ? `0 4px 16px rgba(0,0,0,0.4),0 0 0 3px ${hlColor},0 0 18px ${hlColor}66`
                : `0 2px 10px rgba(0,0,0,0.28),0 0 0 1.5px ${color}44`;
        });

        return el;
    }

    // ── Clear all markers ────────────────────────────────────
    function clearMarkers() {
        markers.forEach(m => m.map = null);
        markers = [];
        highlightedMin = null;
        highlightedMax = null;
        if (activeInfoWindow) { activeInfoWindow.close(); activeInfoWindow = null; }
    }

    // ── Build markers ────────────────────────────────────────
    function buildMarkers(sites, min, max) {
        clearMarkers();
        if (!sites.length) return;

        markers = sites.map(site => {
            const m = new google.maps.marker.AdvancedMarkerElement({
                position: { lat: site.lat, lng: site.lng },
                map,
                title:    site.name + ' — $' + site.price.toFixed(2) + '/L',
                content:  makePinEl(site.price, min, max, site.brand),
            });
            m._site = site;
            m._min  = min;
            m._max  = max;

            m.addListener('click', () => {
                if (activeInfoWindow) activeInfoWindow.close();
                const color   = priceColor(site.price, min, max);
                const logoUrl = BRAND_LOGOS[site.brand];
                const logoHtml = logoUrl
                    ? `<img src="${logoUrl}" style="width:36px;height:36px;flex-shrink:0;object-fit:contain;border-radius:8px;border:1px solid #e2e8f0;padding:3px;background:#fff;" onerror="this.style.display='none'">`
                    : `<div style="width:36px;height:36px;flex-shrink:0;border-radius:8px;background:${color};display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;font-weight:800;font-family:system-ui,sans-serif;">${(site.brand||'?').charAt(0).toUpperCase()}</div>`;

                const fullAddr = [site.addr, `${site.suburb} QLD ${site.postcode}`]
                    .filter(Boolean).join(', ');

                const iw = new google.maps.InfoWindow({
                    maxWidth: 290,
                    content: `
                    <div style="font-family:system-ui,-apple-system,sans-serif;width:270px;margin:-4px -8px;">

                        <!-- Header: logo + name + brand -->
                        <div style="display:flex;align-items:center;gap:11px;padding:14px 16px 11px">
                            ${logoHtml}
                            <div style="min-width:0">
                                <div style="font-weight:700;font-size:14px;color:#0f172a;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${site.name}</div>
                                <div style="font-size:11px;color:#94a3b8;font-weight:500;margin-top:1px">${site.brand || ''}</div>
                            </div>
                        </div>

                        <!-- Full address -->
                        <div style="padding:0 16px 12px">
                            <div style="font-size:11px;color:#64748b;line-height:1.6">${fullAddr}</div>
                        </div>

                        <div style="height:1px;background:#f1f5f9"></div>

                        <!-- Selected fuel type + price -->
                        <div style="padding:12px 16px 10px;background:#fafafa">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px">
                                <span style="font-size:9px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.08em;background:#eef2ff;padding:2px 8px;border-radius:999px">${currentFuelTypeName}</span>
                                <span style="font-size:10px;color:#94a3b8">Updated ${formatUpdated(site.updated)}</span>
                            </div>
                            <div style="display:flex;align-items:baseline;gap:3px">
                                <span style="font-size:38px;font-weight:900;color:${color};line-height:1;letter-spacing:-1px">$${site.price.toFixed(2)}</span>
                                <span style="font-size:13px;color:#94a3b8;font-weight:600">/L</span>
                            </div>
                        </div>

                        <div style="height:1px;background:#f1f5f9"></div>

                        <!-- Secondary prices fine print -->
                        <div style="display:flex;gap:0;padding:10px 16px">
                            ${pricePill('Unleaded', site.price_ul, priceColor(site.price_ul, min, max))}
                            <div style="width:1px;background:#f1f5f9;margin:0 4px"></div>
                            ${pricePill('P. ULP 95', site.price_95, priceColor(site.price_95, min, max))}
                            <div style="width:1px;background:#f1f5f9;margin:0 4px"></div>
                            ${pricePill('P. ULP 98', site.price_98, priceColor(site.price_98, min, max))}
                        </div>

                    </div>`,
                });
                iw.open({ map, anchor: m });
                activeInfoWindow = iw;
            });

            return m;
        });

        updateHighlights();
    }

    // ── Highlight cheapest & priciest in current viewport ────
    function updateHighlights() {
        if (!map || !markers.length) return;
        const bounds = map.getBounds();
        if (!bounds) return;

        let minMarker = null, maxMarker = null;
        let minPrice = Infinity, maxPrice = -Infinity;

        markers.forEach(m => {
            if (bounds.contains(m.position)) {
                if (m._site.price < minPrice) { minPrice = m._site.price; minMarker = m; }
                if (m._site.price > maxPrice) { maxPrice = m._site.price; maxMarker = m; }
            }
        });

        // If only one visible site, skip both (same marker would get two badges)
        if (minMarker === maxMarker) { minMarker = null; maxMarker = null; }

        // Reset cheapest
        if (highlightedMin !== minMarker) {
            if (highlightedMin) {
                const s = highlightedMin._site;
                highlightedMin.content = makePinEl(s.price, highlightedMin._min, highlightedMin._max, s.brand);
                highlightedMin.zIndex  = null;
            }
            highlightedMin = minMarker;
            if (minMarker) {
                const s = minMarker._site;
                minMarker.content = makePinEl(s.price, minMarker._min, minMarker._max, s.brand, 'cheapest');
                minMarker.zIndex  = 1000;
            }
        }

        // Reset priciest
        if (highlightedMax !== maxMarker) {
            if (highlightedMax) {
                const s = highlightedMax._site;
                highlightedMax.content = makePinEl(s.price, highlightedMax._min, highlightedMax._max, s.brand);
                highlightedMax.zIndex  = null;
            }
            highlightedMax = maxMarker;
            if (maxMarker) {
                const s = maxMarker._site;
                maxMarker.content = makePinEl(s.price, maxMarker._min, maxMarker._max, s.brand, 'priciest');
                maxMarker.zIndex  = 999;
            }
        }
    }

    // ── Initialise Google Map ────────────────────────────────
    async function initMap() {
        const { Map }         = await google.maps.importLibrary('maps');
        await google.maps.importLibrary('marker');
        const { Autocomplete } = await google.maps.importLibrary('places');

        map = new Map(document.getElementById('fuelMap'), {
            center:            { lat: -27.4698, lng: 153.0251 },
            zoom:              8,
            mapId:             'DEMO_MAP_ID',
            mapTypeControl:    false,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControlOptions: { position: google.maps.ControlPosition.RIGHT_CENTER },
        });

        buildMarkers(initial.sites, initial.min, initial.max);

        map.addListener('idle', updateHighlights);

        // ── Address search (Places Autocomplete) ──────────────
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

    // ── Livewire event — fuel type changed ───────────────────
    $wire.on('markersUpdated', ({ sites, min, max, fuelTypeName }) => {
        currentFuelTypeName = fuelTypeName ?? '';
        buildMarkers(sites, min, max);
    });
</script>
@endscript
