<?php

namespace AgenterLab\AGWS;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\HttpClientException;
use Firebase\JWT\JWT;

class Client 
{

    /**
     * @var string
     */
    private $clientName;

    /**
     * @var string
     */
    private $tokenName;

    /**
     * @var array
     */
    private $services;

    /**
     * @var string
     */
    private $jwtToken;

    /**
     * @var string
     */
    private $privateKeyPath;

    /**
     * @var string
     */
    private $privateKey;

    /**
     * @param string $clientName
     * @param string $tokenName
     * @param array $services
     */
    function __construct(
        string $clientName,
        string $tokenName,
        array $services,
        string $privateKeyPath
    ) {

       $this->clientName = $clientName;
       $this->tokenName = $tokenName;
       $this->services = $services;
       $this->privateKeyPath = $privateKeyPath;
    }

    /**
     * Generate token
     * 
     * @param int|string $id
     * @param int|string $cid
     * @param bool $force
     * 
     * @return \AgenterLab\HttpClient\Client
     */
    public function token($id, $cid, bool $force = false) {

        if (is_null($this->jwtToken) || $force == true) {

            $payload = [
                "sub" => $id,
                "cid" => $cid,
                "iat" => time(),
                "nbf" => time(),
                'exp' => time() + $this->ttl()
            ];
            
            $this->jwtToken = JWT::encode($payload, $this->getPrivateKey(), 'RS256', $this->clientName);
        }

        return $this;
    }

    /**
     * Get Http client
     * 
     * @param string $serviceName
     * @param string $url
     * 
     * @return \Illuminate\Support\Facades\Http
     */
    public function call(string $serviceName) {

        if (empty($this->services[$serviceName])) {
            throw new HttpClientException("Service not defined");
        }

        $service = $this->services[$serviceName];

        $http = Http::withOptions([
            'verify' => false
        ])
        ->baseUrl($service['url'])
        ->withHeaders([
            $this->tokenName => $this->clientName . ':' . $this->getToken()
        ])->acceptJson();

        return $http;
    }

    /**
     * Get token
     * 
     * @return string
     */
    private function getToken(): string {

        if (empty($this->jwtToken)) {
            throw new HttpClientException("Http client token empty");
        }

        return $this->jwtToken;
    }

    /**
     * Get private key
     * 
     * @return string
     */
    private function getPrivateKey(): string {

        if (is_null($this->privateKey)) {
            
            if (!is_file($this->privateKeyPath)) {
                throw new \InvalidArgumentException('Invalid key path');
            }

            $this->privateKey = file_get_contents($this->privateKeyPath);
        }

        return $this->privateKey;
    }

    /**
     * Get ttl
     */
    private function ttl(): int {
        return 600;
    }

}