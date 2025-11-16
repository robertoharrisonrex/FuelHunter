<div>
    <form  method="POST" wire:keydown="getResults">
        @csrf
        <div class="flex items-center gap-x-2">

            <x-form-input wire:model="search" id="search" name="search" type="search"></x-form-input>
            <x-general-button newClass="relative inline-flex items-center px-2 py-2 -ml-px text-sm font-medium
            text-black bg-white hover:bg-blue-100 border border-blue-300 rounded leading-5
            hover:text-blue-400 focus:z-10 focus:outline-none focus:ring ring-blue-300
            focus:border-blue-300 active:bg-blue-100 active:text-blue-500 transition ease-in-out duration-150">Search</x-general-button>
        </div>
    </form>
    <ul role="list" class="divide-y divide-gray-100">
        @foreach($fuelSites as $fuelSite)
            <li class="flex justify-between gap-x-6 py-5">
                <div class="flex min-w-0 gap-x-4">
                    <div class="min-w-0 flex-auto">
                        <a class="hover:underline" href="/fuel/{{$fuelSite->id}}"><p class="text-sm font-semibold leading-6 text-gray-900">{{$fuelSite->name}}</p></a>
                        <p class="mt-1 truncate text-xs leading-5 text-gray-500">{{$fuelSite->address . ", " . $fuelSite->suburb->name . ", " . $fuelSite->city->name . ", " . $fuelSite->postcode}}</p>
                    </div>
                </div>
                <div class="hidden shrink-0 sm:flex sm:flex-col sm:items-end">
                    <p class="text-sm leading-6 text-gray-900">{{ucwords(strtolower($fuelSite->state->name))}}</p>
                    <p class="mt-1 text-xs leading-5 text-gray-500">Last Updated {{ $fuelSite->api_last_modified ? date('D, d M Y g:i A ', $fuelSite->api_last_modidied) : "2024"}} </p>
                </div>
            </li>
        @endforeach
    </ul>
</div>
