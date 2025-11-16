<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return view('tool.index');
    }

    /**
     * Store a newly created resource in storage.
     * @throws GuzzleException
     */
    public function store(Request $request)
    {
//        //
//                $fuelCall = Http::withHeaders([
//            'Accept' => 'application/json',
//            'Authorization' => env("FUEL_API_TOKEN")
//        ])->get('https://fppdirectapi-prod.fuelpricesqld.com.au/Subscriber/GetCountryGeographicRegions?countryId=21&countryId=21');

        $fuelClient = new Client([
            'base_uri' => env('FUEL_API_URL') ,
            'headers' => [
                'Accept'     => 'application/json',
                'Authorization' => env("FUEL_API_TOKEN")
            ]
        ]);

       $response =  $fuelClient->request('GET', '/Subscriber/GetFullSiteDetails', ['query' => ['countryId' => '21', 'geoRegionLevel' => '3', 'geoRegionId' => '1']]);

        dd(json_decode($response->getBody()->getContents(), TRUE));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
