<?php

namespace Larksuit\SDK;

use Illuminate\Support\ServiceProvider;


class LarksuitServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/larksuit.php', 'larksuit'
        );
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/larksuit.php' => config_path('larksuit.php'),
        ]);
    }
}
