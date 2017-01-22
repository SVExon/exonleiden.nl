<?php

namespace SVExon\Http\Controllers;

use Illuminate\Http\Request;
use SVExon\Http\HttpUtils;
use SVExon\SafeURL;


class LeidenFeedController extends Controller {

    const FACEBOOK_BASE_URL = "https://graph.facebook.com/v2.8";
    const FACEBOOK_FEED_URL = self::FACEBOOK_BASE_URL . "/exonleiden/feed";
    const ERROR_JSON = "{\"error\": \"Failed connection\"}";

    private $url_params = NULL;

    private function url_params() {
        if (!isset($this->url_params)) {
            $this->url_params = array(
                "limit" => env("FACEBOOK_FEED_POSTS_PER_PAGE", 25),
                "access_token" => env("FACEBOOK_ACCESS_TOKEN"),
                "locale" => "nl"
            );
            if (empty($this->url_params["access_token"])) {
                throw new \Exception("FACEBOOK ACCESS TOKEN must be set in the environment!");
            }
        }
        return $this->url_params;
    }

    private function batch_url_params($batch_json) {
        // Trigger the access token to be present
        $this->url_params();
        return array(
            "access_token" => $this->url_params["access_token"],
            "locale" => $this->url_params["locale"],
            "include_headers" => "false",
            "batch" => json_encode($batch_json)
        );
    }

    public function get() {
        $web_output = HttpUtils::makeRequest(self::FACEBOOK_FEED_URL, $this->url_params());
        if (isset($web_output)) {
            $web_json = json_decode($web_output, TRUE);
            $output_json = array(
                "feed" => $this->handleFeedJSON($web_json)
            );
            $paging = $this->handlePaging($web_json);
            if (isset($paging)) {
                foreach ($paging as $key => $value) {
                    $output_json[$key] = $value;
                }
            }
            return $output_json;
        }
        return self::ERROR_JSON;
    }

    public function post(Request $request) {
        if ($request->exists("new_uuid") && $request->exists("old_uuid")) {
            $new_uuid = urldecode($request->new_uuid);
            $old_uuid = urldecode($request->old_uuid);
            $objects = SafeURL::where("uuid", $old_uuid)->orWhere("uuid", $new_uuid)->get();
            if (count($objects) == 2) {
                // Build the batch request
                $is_new = $objects->first()->uuid == $new_uuid;
                $new_url = parse_url($is_new ? $objects->first()->url : $objects->last()->url);
                $old_url = parse_url($is_new ? $objects->last()->url : $objects->first()->url);
                $batch_json = array(
                    array(
                        "method" => "GET",
                        "relative_url" => $new_url["path"] . "?" . $new_url["query"]
                    ),
                    array(
                        "method" => "GET",
                        "relative_url" => $old_url["path"] . "?" . $old_url["query"]
                    )
                );

                $url_params = $this->batch_url_params($batch_json);
                // Handle the response
                $response_json = HttpUtils::makeRequest(self::FACEBOOK_BASE_URL, $url_params, HttpUtils::POST);
                if (isset($response_json)) {
                    $response_json = json_decode($response_json, TRUE);
                    $new_json = json_decode($response_json[0]["body"], TRUE);
                    $old_json = json_decode($response_json[1]["body"], TRUE);
                    unset($response_json);
                    // Merge the feeds into a single object
                    $output_json = array(
                        "new" => $this->handleFeedJSON($new_json),
                        "old" => $this->handleFeedJSON($old_json)
                    );
                    // Handle the paging
                    $paging_new = $this->handlePaging($new_json, TRUE, FALSE);
                    if (isset($paging_new)) $output_json["new_uuid"] = $paging_new["new_uuid"];
                    else $output_json["new_uuid"] = $request->new_uuid;

                    $paging_old = $this->handlePaging($old_json, FALSE, TRUE);
                    if (isset($paging_old)) $output_json["old_uuid"] = $paging_old["old_uuid"];
                    else $output_json["old_uuid"] = $request->old_uuid;

                    return json_encode($output_json);
                }
            }
        }
        return self::ERROR_JSON;
    }

    private function handleFeedJSON($feed_json) {
        $json_object = array();
        // Collect id's and get basic information out of the feed
        $message_ids = array();
        foreach ($feed_json["data"] as $post) {
            array_push($json_object, $post);
            // Collect the id for a batch request
            array_push($message_ids, array(
                "method" => "GET",
                "relative_url" => $post["id"] . "/attachments"
            ));
        }
        // Release the earlier feed, as we don't need it
        unset($feed_json);
        if (!empty($message_ids)) {
            // Do the batch request
            $url_params = $this->batch_url_params($message_ids);
            $attachments_json = json_decode(HttpUtils::makeRequest(self::FACEBOOK_BASE_URL, $url_params, HttpUtils::POST),
                TRUE);
            for ($i = 0; $i < count($attachments_json); $i++) {
                $attachments = json_decode($attachments_json[$i]["body"], TRUE);
                if (count($attachments["data"])) {
                    $json_object[$i]["attachments"] = $attachments["data"];
                }
            }
        }
        return $json_object;
    }

    private function handlePaging($paging_json, $return_new = TRUE, $return_old = TRUE) {
        if (isset($paging_json["paging"])) {
            return array(
                "new_uuid" => $return_new ? $this->UUIDForURL($paging_json["paging"]["previous"]) : "",
                "old_uuid" => $return_old ? $this->UUIDForURL($paging_json["paging"]["next"]) : ""
            );
        }
        return null;
    }

    private function UUIDForURL($url) {
        $object = SafeURL::all()->where("url", $url)->first();
        if (!isset($object)) {
            $object = new SafeURL;
            $object->uuid = base64_encode(openssl_random_pseudo_bytes(20));
            $object->url = $url;
            $object->save();
        }
        return urlencode($object->uuid);
    }

}
