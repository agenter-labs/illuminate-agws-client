<?php

namespace Tests\Feature;

use Tests\TestCase;
use AgenterLab\AGWS\Client;

class ClinetTest extends TestCase
{

    public function testClientInstance()
    {
        $this->assertInstanceOf(Client::class, app('agws.client'));
    }

    /**
     * @dataProvider providesToken
     */
    public function testToken($user, $serviceUser, $organization)
    {
        $token = app('agws.client')
            ->setUser($user)
            ->setServiceUser($serviceUser)
            ->setOrganization($organization)
            ->getToken();

        $this->get('user', [
            config('agws.token_name') => config('agws.client_name') . ':' . $token
        ])
        ->seeJsonEquals([
            'aud' => $serviceUser,
            'sub' => $user,
            'org' => $organization,
        ]);
    }

    public function providesToken()
    {
        return [
            [1, 1, 1],
            [1568, 1, 156],
        ];
    }

}