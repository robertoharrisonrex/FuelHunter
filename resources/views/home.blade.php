<x-layout
    :full-bleed="true"
    :seo="[
        'title'       => 'Queensland Fuel Prices Map | FuelHunter',
        'description' => 'Live fuel prices across Queensland. Find the cheapest petrol, diesel and LPG near you.',
    ]"
>
    <x-slot:heading></x-slot:heading>

    <x-slot:head>
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
