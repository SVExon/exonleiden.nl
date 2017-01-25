// String formatter
// Source: http://stackoverflow.com/a/4673436/3554071
if (!String.prototype.format) {
    String.prototype.format = function() {
        var args = arguments;
        return this.replace(/{(\d+)}/g, function(match, number) {
            return typeof args[number] != 'undefined' ? args[number] : match;
        });
    };
}

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

function makeFeedRequest() {
    requestParameters = pagination();
    $method = $.isEmptyObject(requestParameters) ? $.get : $.post;
    $method(FEED_URL, requestParameters, function(data) {
        requestParameters = pagination(data);
        if (data.feed) feedHandlePosts(data.feed, false);
        if (data.new) feedHandlePosts(data.new, true);
        if (data.old) feedHandlePosts(data.old, false);
        if (data.error) feedHandleError();
    }, "json");
}

function feedHandlePosts(feed, isNew) {
    var container = $("#postContainer");
    var method = isNew ? "prepend" : "append";
    $.each(feed, function(idx, post) {
        container[method](function() {
            return feedHandlePost(post);
        });
    });
    container.children().click(function() {
        var url = "https://www.facebook.com/" + $(this).attr("id");
        window.open(url);
    });
}

function feedHandlePost(post) {
    var divPost = $('<div></div>')
        .attr("id", post.id)
        .append(getDateElement(post.created_time));
    if (post.story) divPost.append(getGenericParagraph(post.story, "title"));
    if (post.message) {
        var messageDiv = $("<div></div>").addClass("message");
        var messages = post.message.split("\n");
        $.each(messages, function() {
            if (this.trim().length > 0) {
                messageDiv.append(getGenericParagraph(this));
            }
        });
        divPost.append(messageDiv);
    }
    if (post.attachments) handleAttachments(divPost, post.attachments);
    return divPost;
}

function getGenericParagraph(content, clss) {
    var element = $("<p></p>").text(content);
    if (clss) {
        element.addClass(clss);
    }
    return element;
}

function getDateElement(date) {
    var parsedDate = moment(date);
    var yearDisplay = moment().year() == parsedDate.year() ? "" : "YYYY ";
    var timeText = parsedDate.format("D MMMM " + yearDisplay + "{0} hh:mm");
    return getGenericParagraph(timeText.format("om"), "time");
}

function handleAttachments(container, attachments) {
    $.each(attachments, function() {

    });
}

$(document).ready(function() {
    moment.locale("nl");
    makeFeedRequest();
});


