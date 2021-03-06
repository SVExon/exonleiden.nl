<?php

namespace SVExon\Http;

class HttpUtils {
    const GET = "GET";
    const POST = "POST";
    const PUT = "PUT";
    const DELETE = "DELETE";
    const TRACE = "TRACE";

    static function makeRequest($url, $url_params = array(), $method = self::GET, $headers = array()) {
        if (!isset($headers["Content-type"])) {
            $headers["Content-type"] = "application/x-www-form-urlencoded";
        }
        // Format the headers
        $formatted_headers = "";
        foreach ($headers as $header => $content) {
            $formatted_headers .= $header . ": " . $content . "\r\n";
        }
        // Proper request options
        $request_options = array(
            "http" => array(
                "header" => $formatted_headers,
                "method" => strtoupper($method)
            )
        );
        // Do the request
        $context = stream_context_create($request_options);

        try {
            $web_content = file_get_contents(self::buildURL($url, $url_params), false, $context);
        } catch (\ErrorException $e) {
            return null;
        }

        if ($web_content === FALSE) {
            return null;
        }
        return $web_content;
    }

    static function buildURL($url, $url_params) {
        if (!empty($url_params)) return $url . "?" . http_build_query($url_params);
        return $url;
    }

    static function makeJsonRequest($url, $url_params = array(), $method = self::GET, $headers = array()) {
        $web_content = self::makeRequest($url, $url_params, $method, $headers);
        if (isset($web_content)) {
            return json_decode($web_content, TRUE);
        }
        return array();
    }


}


