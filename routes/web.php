<?php

Route::get('/', function () {
    $feedUrl = action('LeidenFeedController@get');
    return View::make('index', compact('feedUrl'));
});
