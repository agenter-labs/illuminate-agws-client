<?php

namespace AgenterLab\AGWS;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\HttpClientException;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Cache\Repository;

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
    private $privateKeyPath;

    /**
     * @var string
     */
    private $privateKey;

    /**
     * @var int
     */
    private $organization;

    /**
     * @var int
     */
    private $serviceUser;

    /**
     * @var int
     */
    private $user;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private Repository $repository;

    /**
     * @param string $clientName
     * @param string $tokenName
     * @param \Illuminate\Contracts\Cache\Repository $repository
     * @param array $services
     */
    function __construct(
        string $clientName,
        string $tokenName,
        Repository $repository,
        int $ttl,
        array $services,
        string $privateKeyPath
    ) {
       $this->repository = $repository;
       $this->clientName = $clientName;
       $this->tokenName = $tokenName;
       $this->services = $services;
       $this->privateKeyPath = $privateKeyPath;
       $this->ttl = $ttl;
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
    public function getToken(): string {

        $jwtToken = $this->repository->remember(
        'agws_' . $this->tokenKey(), 
        $this->ttl, 
        function () {

            $time = time();
            $payload = $this->getPayload();
            $payload['exp'] = $time + $this->ttl;

            return JWT::encode(
                $payload, 
                $this->getPrivateKey(), 
                'RS256',
                $this->clientName
            );
        });
        
        return $jwtToken;
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
     * Set User
     */
    public function setUser(int $user) {
        $this->user = $user;
        return $this;
    }

    /**
     * Set service User
     */
    public function setServiceUser(int $serviceUser) {
        $this->serviceUser = $serviceUser;
        return $this;
    }

    /**
     * Set service User
     */
    public function setOrganization(int $organization) {
        $this->organization = $organization;
        return $this;
    }

    /**
     * Get token key
     */
    private function tokenKey(): string
    {
       return implode('-', [
            $this->clientName,
            $this->user ?: 'N',
            $this->serviceUser ?: 'N',
            $this->organization ?: 'N'
        ]);
    }

    /**
     * Get token payload
     */
    private function getPayload(): array
    {
        $payload = [
            'iss' => $this->clientName
        ];

        if ($this->user) {
            $payload['sub'] = $this->user;
        }
        
        if ($this->serviceUser) {
            $payload['aud'] = $this->serviceUser;
        }
        
        if ($this->organization) {
            $payload['org'] = $this->organization;
        }

        return $payload;
    }

}