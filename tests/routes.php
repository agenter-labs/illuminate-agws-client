<?php

$router->group(['middleware' => ['agws-auth']], function () use ($router) {
    $router->get('user', function() {
        return [
            'sub' => app('agws.request')->user(),
            'aud' => app('agws.request')->serviceUser(),
            'org' => app('agws.request')->organization()
        ];
    });
});