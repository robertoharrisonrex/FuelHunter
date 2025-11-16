<x-layout>
    <x-slot:heading>
        Create User
    </x-slot:heading>
    <h1 class="text-lg mb-4 font-bold">Account Details</h1>

    <form id="register" method="POST" action="/register">
        @csrf
        <div class="space-y-12">
            <div class="border-b border-gray-900/10 pb-12">

                <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                    <x-form-field>
                        <x-form-label for="first_name">First Name</x-form-label>
                        <div class="mt-2">
                            <x-form-input name="first_name" id="first_name"/>
                            <div>
                                <x-form-error name="first_name"/>
                            </div>
                        </div>
                    </x-form-field>
                    <x-form-field>
                        <x-form-label for="last_name">Last Name</x-form-label>
                        <div class="mt-2">
                            <x-form-input name="last_name" id="last_name"/>
                            <div>
                                <x-form-error name="last_name"/>
                            </div>
                        </div>
                    </x-form-field>
                    <x-form-field>
                        <x-form-label for="admin">Admin</x-form-label>
                        <div class=" max-w-10 mt-2" >
                            <input type="checkbox" name="admin" id="admin"/>
                        </div>
                    </x-form-field>
                    <x-form-field>
                        <x-form-label for="email">Email</x-form-label>
                        <div class="mt-2">
                            <x-form-input name="email" id="email"/>
                            <div>
                                <x-form-error name="email"/>
                            </div>
                        </div>
                    </x-form-field>
                    <x-form-field>
                        <x-form-label for="password">Password</x-form-label>
                        <div class="mt-2">
                            <x-form-input name="password" id="password"/>
                            <x-form-error name="password"/>
                        </div>
                    </x-form-field>>
                    <x-form-field>
                        <x-form-label for="password_confirmation">Confirm Password</x-form-label>
                        <div class="mt-2">
                            <x-form-input name="password_confirmation" id="password_confirmation"/>
                            <div>
                                <x-form-error name="password_confirmation"/>
                            </div>
                        </div>
                    </x-form-field>
                </div>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-center gap-x-6">
            <a href="/" class="text-sm font-semibold leading-6 text-gray-900">Cancel</a>
            <x-form-button type="submit" for="register">
                Create User
            </x-form-button>
        </div>
    </form>


</x-layout>
