<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function edit()
    {
        return view('profile.edit', ['user' => Auth::user()]);
    }

    /**
     * Display the specified resource.
     * @throws ConnectionException
     */
    public function show()
    {

//        $fuelCall = Http::withHeaders([
//            'Accept' => 'application/json',
//            'Authorization' => 'FPDAPI SubscriberToken=4206dcf3-b2b9-4a70-ac5c-41056f876688'
//        ])->get('https://fppdirectapi-prod.fuelpricesqld.com.au/Subscriber/GetFullSiteDetails?countryId=21&geoRegionLevel=1&geoRegionId=178');
//
//        dd($fuelCall->collect());

//            ->withQueryParameters([
//            'countryId' => 21,
//            'geoRegionLevel' => 178,
//            'geoRegionId' => 1
//        ])->

        return view('profile.show', ['user' => Auth::user()]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {

        $attributes = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'address' => ['nullable','min:3','max:255'],
            'country' => ['nullable','min:3','max:255'],
            'phone_number' => ['nullable','min:8', 'max:255'],
            'website' => ['nullable','url'],
            'logo' => ['nullable', File::types(['png', 'jpg', 'webp'])],
        ]);


        if ($request->newsletter){
            $attributes['newsletter'] = 1;
        } else {
            $attributes['newsletter'] = 0;
        }

        if ($request->logo){
            $logoPath = $request->file('logo')->store('logo', 'public');
            $attributes['logo'] = $logoPath;
            $attributes['logo_local'] = 1;
        }


       Auth::user()->update($attributes);

        return redirect('profile/edit');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
