var requestParameters = null;

function pagination(object) {
    var container = $("#postContainer");
    var old_uuid = container.attr("old_uuid");
    var new_uuid = container.attr("new_uuid");
    if (object && object.old_uuid && object.new_uuid) {
        old_uuid = object.old_uuid;
        container.attr("old_uuid", old_uuid);
        new_uuid = object.new_uuid;
        container.attr("new_uuid", new_uuid);
    } else if (!old_uuid && !new_uuid) {
        return {};
    }
    return {"old_uuid": old_uuid, "new_uuid": new_uuid};
}

function feedHandleError() {

}

function feedHandlePosts(feed, isNew) {
 console.log("x");
}

function makeFeedRequest() {
    requestParameters = pagination();
    $method = $.isEmptyObject(requestParameters) ? $.get : $.post;
    $method(FEED_URL, requestParameters, function(data) {
        requestParameters = pagination(data);
        if (data.feed) feedHandlePosts(data.feed);
        if (data.new) feedHandlePosts(data.new, true);
        if (data.old) feedHandlePosts(data.new);
        if (data.error) feedHandleError();
    }, "json");
}


$(document).ready(function() {
    makeFeedRequest();
});




