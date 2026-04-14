<?php

use App\Http\Controllers\FuelSiteController;
use App\Http\Controllers\MapDataController;
use App\Http\Controllers\MapHeatmapController;
use App\Http\Controllers\MapStatsController;
use App\Http\Controllers\MapTileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisteredUserController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\ToolController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home');
Route::view('/about', 'about');
Route::view('/dashboard', 'dashboard.index');

Route::get('/map-data/{fuelTypeId}', [MapDataController::class, 'show']);
Route::get('/map-stats/{fuelTypeId}', [MapStatsController::class, 'show']);
Route::get('/map-tiles/{fuelTypeId}/{latTile}/{lngTile}', [MapTileController::class, 'show'])->where(['latTile' => '-?\d+', 'lngTile' => '-?\d+']);
Route::get('/map-heatmap/{fuelTypeId}',               [MapHeatmapController::class, 'cities']);
Route::get('/map-heatmap/{fuelTypeId}/city/{cityId}', [MapHeatmapController::class, 'suburbs']);

Route::controller(FuelSiteController::class)->group(function() {
    Route::get('/fuel', 'index');
    Route::get('/fuel/{fuelSite}', 'show');
    Route::post('/fuel', 'filter');


});


Route::controller(RegisteredUserController::class)->group(function () {
    Route::get('/register', 'create');
    Route::post('/register', 'store');
});

Route::controller(SessionController::class)->group(function () {
    Route::get('/login', 'create')->name('login');
    Route::post('/login', 'store');
    Route::post('/logout', 'destroy');

});

Route::controller(ProfileController::class)->group(function () {
    Route::get('/profile', 'show')->middleware('auth');
   Route::get('/profile/edit', 'edit')->middleware('auth');
   Route::patch('/profile/edit', 'update')->middleware('auth');
   Route::delete('/profile/delete', 'destroy')->middleware('auth');

});

Route::controller(ToolController::class)->group(function () {
    Route::get('/tool', 'index')->middleware('auth');
    Route::post('/tool', 'store')->middleware('auth');
    Route::get('/tool/edit', 'edit')->middleware('auth');
    Route::patch('/tool/edit', 'update')->middleware('auth');
    Route::delete('/tool/delete', 'destroy')->middleware('auth');

});



