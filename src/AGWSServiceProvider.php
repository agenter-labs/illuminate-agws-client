<?php

namespace AgenterLab\AGWS;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AGWSServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton('agws.client', function ($app) {

            return new Client(
                $app['config']->get('agws.client_name'),
                $app['config']->get('agws.token_name'),
                $app['config']->get('agws.services'),
                $app['config']->get('agws.private_key_path')
            );
        });

        $this->mergeConfigFrom(__DIR__ . '/../config/agws.php', 'agws');
    }
}
