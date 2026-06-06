<x-layout
    :full-bleed="true"
    :seo="[
        'title'       => 'Queensland Fuel Prices Map | FuelHunter',
        'description' => 'Live fuel prices across Queensland. Find the cheapest petrol, diesel and LPG near you.',
    ]"
>
    <x-slot:heading></x-slot:heading>

    <x-slot:head>
        {{-- Google Maps bootstrap --}}
        <script>(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await(a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})(
{key: "{{ config('services.google.maps_api_key') }}", v: "beta"});</script>
        <style>
#fuelMapWrapper { height: calc(100dvh - 120px); min-height: 360px; }
@media (min-width: 768px) { #fuelMapWrapper { height: calc(100vh - 92px); min-height: 520px; } }
.pac-container { background: rgba(255,255,255,0.97); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 10px 40px rgba(0,0,0,0.12), 0 0 0 1px rgba(99,102,241,0.08); margin-top: 6px; padding: 4px; font-family: system-ui, -apple-system, sans-serif; overflow: hidden; }
.pac-item { padding: 9px 12px; color: #475569; border-color: #f1f5f9; cursor: pointer; border-radius: 10px; margin: 1px 0; transition: background 0.12s; font-size: 13px; }
.pac-item:hover,.pac-item-selected { background: #eef2ff; }
.pac-item-query { color: #0f172a; font-size: 13px; font-weight: 600; }
.pac-matched { color: #6366f1; } .pac-icon { display: none; } .pac-logo { padding: 4px 12px 6px; }
html.dark .pac-container { background: rgba(15,23,42,0.97); border-color: #334155; }
html.dark .pac-item { color: #94a3b8; border-color: #1e293b; }
html.dark .pac-item:hover, html.dark .pac-item-selected { background: #1e293b; }
html.dark .pac-item-query { color: #f1f5f9; }
.gm-style .gm-style-iw-c { padding:0 !important; border-radius:16px !important; box-shadow:0 8px 32px rgba(0,0,0,0.14) !important; }
.gm-style .gm-style-iw-d { overflow:hidden !important; } .gm-style .gm-style-iw-chr { display:none !important; } .gm-style .gm-style-iw-t::after { display:none !important; }
.fuel-pin { transition: transform .14s ease, box-shadow .14s ease; }
.fuel-pin:hover { transform: var(--scale-h) !important; box-shadow: var(--shadow-h) !important; }
.locate-me-btn { bottom: 70px; right: 10px; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.15s, border-color 0.15s, box-shadow 0.15s; }
.locate-me-btn[data-state="idle"] { background: #1e293b; border: 1px solid #334155; color: #94a3b8; box-shadow: 0 2px 8px rgba(0,0,0,0.4); }
.locate-me-btn[data-state="loading"] { background: #1e293b; border: 1px solid #0ea5e9; color: #0ea5e9; box-shadow: 0 2px 8px rgba(14,165,233,0.2); }
.locate-me-btn[data-state="active"] { background: #0ea5e9; border: 1px solid #38bdf8; color: white; box-shadow: 0 2px 12px rgba(14,165,233,0.5); }
.locate-me-btn[data-state="error"] { background: #1e293b; border: 1px solid #ef4444; color: #ef4444; box-shadow: 0 2px 8px rgba(239,68,68,0.2); }
@keyframes locatePulse { 0% { box-shadow: 0 0 0 0 rgba(59,130,246,0.4); } 70% { box-shadow: 0 0 0 8px rgba(59,130,246,0); } 100% { box-shadow: 0 0 0 0 rgba(59,130,246,0); } }
        </style>
        @php
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebSite',
            'name'        => 'FuelHunter',
            'url'         => config('app.url'),
            'description' => 'Track live fuel prices across Queensland.',
        ];
        $orgSchema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => 'FuelHunter',
            'url'      => config('app.url'),
            'logo'     => rtrim(config('app.url'), '/') . '/logo.png',
        ];
        @endphp
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        <script type="application/ld+json">{!! json_encode($orgSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    </x-slot:head>

    <livewire:fuel-map />
</x-layout>
