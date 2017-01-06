<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <title>SV Exon</title>
    </head>
    <body>
        <div old_uuid="{{ $feed["old_uuid"] }}" new_uuid="{{ $feed["new_uuid"] }}">
            @if (empty($feed["feed"]))
                @for ($i = 0; $i < env("FACEBOOK_FEED_POSTS_PER_PAGE"); $i++)
                    <div class="facebook-post">
                        <p>Kon niet verbinden met de facebook server</p>
                    </div>
                @endfor
            @else
                @foreach ($feed["feed"] as $post)
                    <div class="facebook-post" post_id="{{ $post["id"] }}">
                        <script>showTime("{{ $post["created_time"] }}");</script>
                        @if (isset($post["story"]))
                            <p>{{ $post["story"] }}</p>
                        @endif
                        @if (isset($post["message"]))
                            <p>{{ $post["message"] }}</p>
                        @endif
                        @if (isset($post["attachments"]))
                            <script>showAttachments('{!! json_encode($post["attachments"]) !!}')</script>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </body>
</html>