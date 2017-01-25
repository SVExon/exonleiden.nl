<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <title>SV Exon</title>
        <script src="{{ URL::asset('js/jquery-3.1.1.min.js') }}"></script>
        <script src="{{ elixir('js/index.min.js') }}"></script>
        <script>
            var FEED_URL = '{{ $feedUrl }}';
        </script>
    </head>
    <body>
        <div id="postContainer">
        </div>
    </body>
</html>