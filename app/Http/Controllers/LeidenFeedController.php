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

    private function urlParams() {
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

    private function batchUrlParams(array $batch_json) {
        // Trigger the access token to be present
        $this->urlParams();
        return array(
            "access_token" => $this->url_params["access_token"],
            "locale" => $this->url_params["locale"],
            "include_headers" => "false",
            "batch" => json_encode($batch_json)
        );
    }

    private function doJsonBatchRequest($format, $data) {
        $batch_build = array();
        foreach ($data as $single_request) {
            $relative_url = "";
            if (is_array($single_request)) {
                array_unshift($single_request, $format);
                $relative_url = call_user_func("sprintf", $single_request);
            } else {
                $relative_url = sprintf($format, $single_request);
            }
            array_push($batch_build, array(
                "method" => "GET",
                "relative_url" => $relative_url
            ));
        }

        $url_params = $this->batchUrlParams($batch_build);
        $content = HttpUtils::makeJsonRequest(self::FACEBOOK_BASE_URL, $url_params, HttpUtils::POST);
        $decoded_array = array();
        foreach ($content as $request) {
            array_push($decoded_array, json_decode($request["body"], TRUE));
        }
        return $decoded_array;
    }

    public function get() {
        $output = HttpUtils::makeJsonRequest(self::FACEBOOK_FEED_URL, $this->urlParams());
        if (!empty($output)) {
            $output_json = array(
                "feed" => $this->handleFeedJSON($output)
            );
            $paging = $this->handlePaging($output);
            if (isset($paging)) {
                foreach ($paging as $key => $value) {
                    $output_json[$key] = $value;
                }
            }
            return response($output_json);
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

                $response_json = $this->doJsonBatchRequest("%s?%s", array(
                    array($new_url["path"], $new_url["query"]),
                    array($old_url["path"], $old_url["query"])
                ));
                // Merge the feeds into a single object
                $output_json = array(
                    "new" => $this->handleFeedJSON($response_json[0]),
                    "old" => $this->handleFeedJSON($response_json[1])
                );
                // Handle the paging
                $paging_new = $this->handlePaging($response_json[0], TRUE, FALSE);
                if (isset($paging_new)) $output_json["new_uuid"] = $paging_new["new_uuid"];
                else $output_json["new_uuid"] = $request->new_uuid;

                $paging_old = $this->handlePaging($response_json[1], FALSE, TRUE);
                if (isset($paging_old)) $output_json["old_uuid"] = $paging_old["old_uuid"];
                else $output_json["old_uuid"] = $request->old_uuid;
                return response(json_encode($output_json));
            }
        }
        return self::ERROR_JSON;
    }

    private function handleFeedJSON(array $feed_json) {
        $json_object = array();
        // Collect id's and get basic information out of the feed
        $message_ids = array();
        foreach ($feed_json["data"] as $post) {
            array_push($json_object, $post);
            // Collect the id for a batch request
            array_push($message_ids, $post["id"]);
        }
        // Release the earlier feed, as we don't need it
        unset($feed_json);
        if (!empty($message_ids)) {

            $attachments_json = $this->doJsonBatchRequest("%s/attachments", $message_ids);
            for ($i = 0; $i < count($attachments_json); $i++) {
                if (count($attachments_json[$i]["data"])) {
                    $json_object[$i]["attachments"] = $attachments_json[$i]["data"];
                    $this->handleAttachments($json_object[$i]["attachments"]);
                }
            }
        }
        return $json_object;
    }

    private function handleAttachments(array &$attachments) {
        $event_id_map = array();
        for ($i = 0; $i < count($attachments); $i++) {
            if($attachments[$i]["type"] == "event") {
                $event_id = array();
                preg_match("(\d+)", $attachments[$i]["url"], $event_id);
                $event_id_map[$event_id[0]] = $i;
            }
        }
        // Check if we even have requests
        if (!empty($event_id_map)) {
            $event_times = $this->doJsonBatchRequest("%s?fields=start_time,end_time", array_keys($event_id_map));
            foreach ($event_times as $event_time) {
                $id = $event_time["id"];
                $attachments[$event_id_map[$id]]["start_time"] = $event_time["start_time"];
                $attachments[$event_id_map[$id]]["end_time"] = $event_time["end_time"];
            }
        }
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
