<x-layout>
    <x-slot:heading>
        Profile
    </x-slot:heading>


    <div>

        <div class="grid">
            <img src="{{ $user->logo_local == 1 ? asset( "storage/" . $user->logo) : $user->logo }}" alt="" class="h-100 w-100 rounded-full ml-auto mr-auto mt-5 mb-1">
            <h3 class="text-base font-semibold leading-7 text-gray-900 ml-auto mr-auto mb-10" >Profile Picture</h3>
        </div>

        <div class="px-4 mt-4 sm:px-0">

            <h3 class="text-base font-semibold leading-7 text-gray-900">Profile Information</h3>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-gray-500">Personal details</p>
        </div>
        <div class="mt-6 border-t ">
            <dl class="divide-y divide-gray-400">
                <x-profile-section label="Full Name">
                    {{trim($user->first_name . " " . $user->last_name)}}
                </x-profile-section>
                <x-profile-section label="Email">
                    {{trim($user->email)}}
                </x-profile-section>

                <x-profile-section label="Phone Number">
                    {{trim($user->phone_number)}}
                </x-profile-section>
                <x-profile-section label="Address">
                    {{trim($user->address)}}
                </x-profile-section>
                <x-profile-section label="Region">
                    {{trim($user->country)}}
                </x-profile-section>
                <x-profile-section label="Website">
                    {{trim($user->website)}}
                </x-profile-section>
                <x-profile-section label="Newsletter">
                    @if($user->newsletter)
                        Subscribed
                    @else
                        Unsubscribed
                    @endif
                </x-profile-section>
            </dl>
            <x-general-link href="/profile/edit" class="hover:border-green-400">Edit Profile</x-general-link>
        </div>
    </div>


</x-layout>
