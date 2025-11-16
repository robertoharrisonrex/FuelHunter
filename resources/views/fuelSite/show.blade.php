<x-layout>
    <x-slot:heading>
        {{$fuelSite->name}}
    </x-slot:heading>
    <ul role="list" class="divide-y divide-gray-100">

        <li class="flex items-center justify-between gap-x-6 py-5">
            <div class="flex min-w-0 gap-x-4">
                <div class="min-w-0 flex-auto">
                    <a class="hover:underline" href="/fuel/{{$fuelSite->id}}"><p class="text-sm font-semibold leading-6 text-gray-900">{{$fuelSite->name}}</p></a>
                    <p class="mt-1 truncate text-xs leading-5 text-gray-500">{{$fuelSite->address . ", " . $fuelSite->suburb->name . ", " . $fuelSite->city->name . ", " . $fuelSite->postcode}}</p>
                </div>
            </div>
            <div class="flex min-w-0 gap-x-4">
                @foreach($fuelSite->prices as $price)

                    @php
                    $testSway = rand(1,2);
                    @endphp

            <article class="flex flex-col rounded-lg border border-gray-100 bg-white p-5 items-center ">
                <div class="inline-flex gap-2 self-end rounded p-1  @if($testSway == 1) {{" bg-green-100 text-green-600"}} @else {{" bg-red-100 text-red-600"}} @endif">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d=" @if($testSway == 1) {{"M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"}} @else {{"M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"}} @endif"
                        />
                        <!-- PRICE INCREASE AKA LINE GOING UP

                           <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                        />
                        -->
                    </svg>

                    <span class="text-xs font-medium"> ${{rand(0.10 , 30) / 10}} </span>
                </div>

                <div class="mt-3">
                    <strong class="block text-sm font-medium text-gray-500"> {{$price->fuelType->name}} </strong>

                    <p class="mt-2">
                        <span class="text-2xl font-medium text-gray-900"> ${{$price->price / 100}}  </span>
                    </p>
                    <p>
                        <span class=" text-xs text-gray-500"> As of {{date('D, d M Y g:i A', strtotime($price->transaction_date_utc))}}  </span>
                    </p>
                </div>
            </article>
                @endforeach

            </div>

            <div class="hidden shrink-0 sm:flex sm:flex-col sm:items-end">
                <p class="text-sm leading-6 text-gray-900">{{ucwords(strtolower($fuelSite->state->name))}}</p>
                <p class="mt-1 text-xs leading-5 text-gray-500">{{$fuelSite->city->name}}</p>
            </div>
        </li>
    </ul>

{{--    <gmp-map center="{{$fuelSite->latitude}},{{$fuelSite->longitude}}" zoom="10" style="height: 400px">--}}
{{--        <gmp-advanced-marker--}}
{{--            position="{{$fuelSite->latitude}},{{$fuelSite->longitude}}"--}}
{{--            title="Test"--}}
{{--        ></gmp-advanced-marker>--}}

{{--    </gmp-map>--}}

    <iframe
        width="1300"
        height="500"
        frameborder="0" style="border:0"
        referrerpolicy="no-referrer-when-downgrade"
        src="https://www.google.com/maps/embed/v1/place?key={{env('GOOGLE_API_TOKEN')}}&q={{$fuelSite->name}}&zoom=14"
        allowfullscreen>
    </iframe>

{{--    <script--}}
{{--        src="https://maps.googleapis.com/maps/api/js?key={{env('GOOGLE_API_TOKEN')}}&loading=async&libraries=maps,marker&v=beta" defer>--}}

{{--    </script>--}}





</x-layout>

