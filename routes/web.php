<?php


Route::get('/', function () {
    $feed = (new \SVExon\Http\FacebookFeed())->get_feed();
    if (isset($feed["error"])) {
        $fake_feed = array(
            "feed" => array(),
            "new_uuid" => "fetch",
            "old_uuid" => "fetch"
        );
        return View::make('index')->with("feed", $fake_feed);
    }
    return View::make('index', compact("feed"));
});
