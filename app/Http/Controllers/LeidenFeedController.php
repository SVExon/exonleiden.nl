<?php

namespace SVExon\Http\Controllers;

use Illuminate\Http\Request;
use SVExon\Http\FacebookFeed;


class LeidenFeedController extends Controller {

    private $facebook_handler;

    public function __construct() {
        $this->facebook_handler = new FacebookFeed();
    }

    public function get() {
        return json_encode($this->facebook_handler->get_feed());
    }

    public function post(Request $request) {
        return json_encode($this->facebook_handler->get_mixed_feed($request));
    }

}
