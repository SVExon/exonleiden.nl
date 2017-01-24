const elixir = require('laravel-elixir');


elixir(function(mix) {
    mix.scripts(['feed_handler.js', 'moment-with-locales.js'], 'public/js/index.min.js')
        .version('public/js/index.min.js');
});
