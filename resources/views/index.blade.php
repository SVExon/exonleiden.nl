<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="utf-8">
        <title>SV Exon</title>
        <link  href="{{ elixir('css/index.min.css') }}" rel="stylesheet" type="text/css"/>
        <script src="{{ URL::asset('js/jquery-3.1.1.min.js') }}"></script>
        <script src="{{ elixir('js/index.min.js') }}"></script>
        <script>
            var FEED_URL = '{{ $feedUrl }}';
        </script>
    </head>
    <body>
        <nav class="fixed">
            <ul>
                <li class="active"><a href>Home</a></li>
                <li class="symbol">&#164;</li>
                <li><a href>Het Bestuur</a></li>
                <li class="symbol">&#164;</li>
                <li><a href>Statuten</a></li>
                <li class="symbol">&#164;</li>
                <li><a href>Contact</a></li>
            </ul>
        </nav>
        <div id="fixedWelcome" class="fixed">
            <div id="logo" class="adjust">
                <img src="{{ URL::asset('image/exon_logo.png') }}"/>
            </div>
        </div>
        <input type="checkbox" id="close"/>
        <div id="studieStore">
            <a href="https://goo.gl/HHziJo" target="_blank">
                <img src="{{ URL::asset('image/study_store_banner.jpg') }}" width="300" height="250">
            </a>
            <label for="close">
                <img src="{{ URL::asset('image/close.png') }}" width="25" height="25"/>
            </label>
        </div>
        <div id="scrollAbove">
            {{--<div id="postContainer"></div>--}}
            {{--<footer>--}}
                {{--<p>Background provided by <a href="https://subtlepatterns.com/page/12/">Devin Holmes</a></p>--}}
            {{--</footer>--}}
        </div>
    </body>
</html>
