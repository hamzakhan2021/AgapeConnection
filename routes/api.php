<?php

use Illuminate\Http\Request;

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
Route::group(['middleware' => ['json.response']], function () {

    Route::middleware('auth:api')->get('/user', function (Request $request) {
        return $request->user();
    });

    // public routes
    Route::post('/login', 'Api\AuthController@login')->name('login.api');
    Route::post('/register', 'Api\AuthController@register')->name('register.api');

    // private routes
    Route::middleware('auth:api')->group(function () {
        Route::apiResources([
            'Users'       => 'Api\UserController',
        ]);
        Route::post('/user/update', 'Api\UserController@updateProfile')->name('update.api');
        Route::post('/upload/profileImage', 'Api\UserController@uploadImage')->name('upload.api');
        Route::post('/search/byName', 'Api\UserController@searchByName')->name('searchName.api');
        Route::get('/logout', 'Api\AuthController@logout')->name('logout');
    });

});
