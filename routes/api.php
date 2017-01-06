<?php

use Illuminate\Http\Request;


//Route::get('/user', function (Request $request) {
//    return $request->user();
//})->middleware('auth:api');

Route::get('fb-feed-exonleiden', 'LeidenFeedController@get');
Route::post('fb-feed-exonleiden', 'LeidenFeedController@post');
