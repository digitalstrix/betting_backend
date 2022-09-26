<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// With authentication routes
Route::post("/register",'App\Http\Controllers\AdminController@register'); 
Route::post("/login",'App\Http\Controllers\AdminController@login'); 
Route::get("/getgame",'App\Http\Controllers\AdminController@getgame'); 
Route::get("/cron",'App\Http\Controllers\AdminController@Cron'); 

// Bearer authentication Secured Routes
Route::group(['middleware' => 'auth:sanctum'], function(){
    Route::post("/updateUser",'App\Http\Controllers\AdminController@updateUser'); 
    Route::post("/addCoin",'App\Http\Controllers\AdminController@addCoin'); 
    Route::post("/siteSetting",'App\Http\Controllers\AdminController@siteSetting'); 
    Route::post("/getUser",'App\Http\Controllers\AdminController@getUser'); 
    Route::post("/Bet",'App\Http\Controllers\AdminController@Bet'); 
    Route::post("/addgame",'App\Http\Controllers\AdminController@addgame');
    Route::post("/getmatch",'App\Http\Controllers\AdminController@getMatch'); 
    Route::post("/earnamount",'App\Http\Controllers\AdminController@earnamount'); 
    Route::post("/addwithdrawl",'App\Http\Controllers\AdminController@addwithdrawl'); 

});