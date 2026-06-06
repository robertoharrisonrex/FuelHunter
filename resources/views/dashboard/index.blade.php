<x-layout :seo="['title' => 'Queensland Fuel Price Trends | FuelHunter', 'description' => 'Track Queensland fuel price trends and statistics over time.']">
    <x-slot:heading>
        Statistics
    </x-slot:heading>

    <x-slot:head>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <style>
            @keyframes fadeUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
            .dash-card { opacity: 0; animation: fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) forwards; }
            .dash-card:nth-child(1) { animation-delay: 0.04s; } .dash-card:nth-child(2) { animation-delay: 0.12s; }
            .dash-card:nth-child(3) { animation-delay: 0.20s; } .dash-card:nth-child(4) { animation-delay: 0.28s; }
            .dash-card:nth-child(5) { animation-delay: 0.36s; } .dash-card:nth-child(6) { animation-delay: 0.44s; }
            .dash-card:nth-child(7) { animation-delay: 0.52s; }
        </style>
    </x-slot:head>

    <livewire:Dashboard />
</x-layout>
