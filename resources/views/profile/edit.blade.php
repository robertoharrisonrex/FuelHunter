<x-layout xmlns="http://www.w3.org/1999/html">
    <x-slot:heading>
        Edit
    </x-slot:heading>

    <!--
      This example requires some changes to your config:

      ```
      // tailwind.config.js
      module.exports = {
        // ...
        plugins: [
          // ...
          require('@tailwindcss/forms'),
        ],
      }
      ```
    -->
    <form method="POST" enctype="multipart/form-data" action="/profile/edit" class="bg-white p-20 rounded-3xl">
        @csrf
        @method('patch')
        <div class="space-y-12">

            <div class="grid">
                <img src="{{ $user->logo_local == 1 ? asset( "storage/" . $user->logo) : $user->logo }}" alt="" class="h-100 w-100 rounded-full ml-auto mr-auto mt-5 mb-1">
                <h3 class="text-base font-semibold leading-7 text-gray-900 ml-auto mr-auto mb-10" >Profile Picture</h3>
            </div>
            <div class="border-b border-gray-900/10 pb-12">
                <h2 class="text-base font-semibold leading-7 text-gray-900">Personal Information</h2>
                <p class="mt-1 text-sm leading-6 text-gray-600">Use a permanent address where you can receive mail.</p>

                <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                    <div class="sm:col-span-3">
                        <label for="first_name" class="block text-sm font-medium leading-6 text-gray-900">First name</label>
                        <div class="mt-2">
                            <input type="text" value="{{$user->first_name}}" name="first_name" id="first_name" class="block w-full pl-2.5 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 hover:ring-1 hover:ring-inset hover:ring-indigo-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <x-form-error name="first_name"/>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="last_name" class="block text-sm font-medium leading-6 text-gray-900">Last name</label>
                        <div class="mt-2">
                            <input type="text" value="{{$user->last_name}}" name="last_name" id="last_name" autocomplete="family-name" class="block pl-2.5 w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 hover:ring-1 hover:ring-inset hover:ring-indigo-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <x-form-error name="last_name"/>
                    </div>
                    <div class="col-span-full">
                        <label for="photo" class="block text-sm font-medium leading-6 text-gray-900">Photo</label>
                        <div class="mt-2 flex items-center gap-x-3">

                            <input type="file" name="logo" id="logo" class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        </div>
                        <x-form-error name="logo"/>
                    </div>

                    <div class="sm:col-span-4">
                        <label for="email" class="block text-sm font-medium leading-6 text-gray-900">Email address</label>
                        <div class="mt-2">
                            <p id="email" type="email" class="block pl-2.5 w-full bg-gray-200 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 hover:ring-1 hover:ring-inset hover:ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">{{$user->email}}</p>
                        </div>
                        <x-form-error name="email"/>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="phone_number" class="block text-sm font-medium leading-6 text-gray-900">Phone Number</label>
                        <div class="mt-2">
                            <input id="phone_number" name="phone_number" value="{{$user->phone_number}}" class="block pl-2.5 w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 hover:ring-1 hover:ring-inset hover:ring-indigo-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <x-form-error name="phone_number"/>
                    </div>

                    <div class="col-span-full">
                        <label for="website" class="block text-sm font-medium leading-6 text-gray-900">Website</label>
                        <div class="mt-2">
                            <input type="text" value="{{$user->website}}" name="website" id="website" class="block pl-2.5 w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 hover:ring-1 hover:ring-inset hover:ring-indigo-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <x-form-error name="website"/>
                    </div>

                    <div class="col-span-full">
                        <label for="address" class="block text-sm font-medium leading-6 text-gray-900">Address</label>
                        <div class="mt-2">
                            <input type="text" value="{{$user->address}}" name="address" id="address" class="block pl-2.5 w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 hover:ring-1 hover:ring-inset hover:ring-indigo-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <x-form-error name="address"/>

                    </div>

                    <div class="sm:col-span-3">
                        <label for="country" class="block text-sm font-medium leading-6 text-gray-900">Region</label>
                        <div class="mt-2">
                            <input id="country" value="{{$user->country}}" name="country"  class="block pl-2.5 w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:ring-1 hover:ring-inset hover:ring-indigo-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:max-w-xs sm:text-sm sm:leading-6">
                        </div>
                        <x-form-error name="country"/>
                    </div>
                </div>
            </div>

            <div class="border-b border-gray-900/10 pb-12">
                <h2 class="text-base font-semibold leading-7 text-gray-900">Notifications</h2>
                <p class="mt-1 text-sm leading-6 text-gray-600">We'll always let you know about important changes, but you pick what else you want to hear about.</p>

                <div class="mt-10 space-y-10">
                    <fieldset>
                        <legend class="text-sm font-semibold leading-6 text-gray-900">By Email</legend>
                        <div class="mt-6 space-y-6">
                            <div class="relative flex gap-x-3">
                                <div class="flex h-6 items-center">
                                    <input id="newsletter" name="newsletter" @if($user->newsletter) checked @endif type="checkbox" class="h-4 w-4 hover:ring-1 hover:ring-inset hover:ring-indigo-600 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                </div>
                                <div class="text-sm leading-6">
                                    <label for="newsletter" class="font-medium text-gray-900">Newsletter</label>
                                    <p class="text-gray-500">Get weekly job newsletters</p>
                                </div>
                                <x-form-error name="newsletter"/>

                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>
        </div>

        <div class="mt-6 flex items-center justify-end gap-x-6">
           <x-general-link class="border-white" href="/profile">Cancel</x-general-link>
            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Save</button>
        </div>
    </form>


</x-layout>
