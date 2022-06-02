<?php

namespace Tests;

use Laravel\Lumen\Testing\TestCase as BaseTestCase;
use Laravel\Lumen\Http\Request as LumenRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Illuminate\Testing\TestResponse;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    static $KEY_GENERATED = false;


    protected function setUp(): void
    {    
        parent::setUp();
        $clientName = config('agws.client_name');
        config([
            'agws.private_key_path' => resource_path('keys/' . $clientName. '.key'),
            'agws.public_key_path' => resource_path('keys'),
            'agws.services' => [
                'id' => [
                    'url' => ''
                ]
            ]
        ]);
        $this->setKey();

        Http::fake();
    }

    private function setKey()
    {
        if (TestCase::$KEY_GENERATED) {
            return;
        }

        $prvKeyFile = config('agws.private_key_path');
        $pubKeyFile = config('agws.public_key_path') . '/' . config('agws.client_name'). '.pub';

        $res = openssl_pkey_new([
            "digest_alg" => "sha512",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);

        // Extract the private key from $res to $privKey
        openssl_pkey_export($res, $privKey);

        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);
        $pubKey = $pubKey["key"];

        file_put_contents($pubKeyFile, $pubKey);

        file_put_contents($prvKeyFile, $privKey);

        TestCase::$KEY_GENERATED = true;
    }
    
    /**
     * Call the given URI and return the Response.
     *
     * @param  string  $method
     * @param  string  $uri
     * @param  array  $parameters
     * @param  array  $cookies
     * @param  array  $files
     * @param  array  $server
     * @param  string  $content
     * @return \Illuminate\Http\Response
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->currentUri = $this->prepareUrlForRequest($uri);

        $symfonyRequest = SymfonyRequest::create(
            $this->currentUri, $method, $parameters,
            $cookies, $files, $server, $content
        );

        $this->app['request'] = LumenRequest::createFromBase($symfonyRequest);

        // $this->app['auth']->setRequest($this->app['request']);

        return $this->response = TestResponse::fromBaseResponse(
            $this->app->prepareResponse($this->app->handle($this->app['request']))
        );
    }
}