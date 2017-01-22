const elixir = require('laravel-elixir');


elixir(function(mix) {
    mix.scripts(['feed_handler.js'], 'public/js/feed_handler.min.js')
        .version('public/js/feed_handler.min.js');
});
