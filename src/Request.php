<?php

namespace AgenterLab\AGWS;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request as HttpRequest;
use AgenterLab\AGWS\Exceptions\RequestException;
use Illuminate\Auth\GenericUser;

class Request 
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
     * @var string
     */
    private $publicKeyPath;

    /**
     * @var string
     */
    private $publicKey;

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
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private Repository $repository;

    /**
     * @var \Illuminate\Http\Request
     */
    private HttpRequest $request;

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Illuminate\Contracts\Cache\Repository $repository
     * @param string $clientName
     * @param string $tokenName
     * @param array $services
     */
    function __construct(
        HttpRequest $request,
        Repository $repository,
        string $tokenName,
        string $publicKeyPath
    ) {
       $this->request = $request;
       $this->repository = $repository;
       $this->tokenName = $tokenName;
       $this->publicKeyPath = rtrim($publicKeyPath, DIRECTORY_SEPARATOR);
    }

    /**
     * Get public key
     * 
     * @return string
     */
    private function getKey(string $keyName): string {

        if (is_null($this->publicKey)) {
            
            $path = $this->publicKeyPath . DIRECTORY_SEPARATOR . $keyName . '.pub';

            if (!is_file($path)) {
                throw new RequestException('Invalid key path');
            }

            $this->publicKey = file_get_contents($path);
        }

        return $this->publicKey;
    }

    /**
     * Get User
     */
    public function user() {
        return $this->user;
    }

    /**
     * Get service User
     */
    public function serviceUser() {
        return $this->serviceUser;
    }

    /**
     * Get service User
     */
    public function organization() {
        return $this->organization;
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
     * Validate client token
     */
    public function validate() {

        $token = $this->request->headers->get($this->tokenName);

        if (!$token) {
            return;
            // throw new RequestException("Request access token must not be empty");
        }

        list($clientName, $jwt) = array_pad(explode(':', $token, 2), 2, null);

        if (!$clientName || !$jwt) {
            throw new RequestException('Access token invalid format');
        }

        $decoded = JWT::decode($jwt, new Key($this->getKey($clientName), 'RS256'));

        $this->user = $decoded?->sub;
        $this->serviceUser = $decoded?->aud;
        $this->organization = $decoded?->org;
    }
    
}