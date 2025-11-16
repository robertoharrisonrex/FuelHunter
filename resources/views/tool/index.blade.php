<x-layout>
    <x-slot:heading>
        Tools
    </x-slot:heading>
    <h1>Tools</h1>
</x-layout>

<div>
    <form method="post" id="request" action="/tool">
        @csrf
        <x-form-button type="submit"
                       classes="text-gray-300 hover:bg-gray-700 hover:text-white rounded-md px-3 py-2 text-sm font-medium">
            Pull in API Data to Databases
        </x-form-button>
    </form>
</div>
