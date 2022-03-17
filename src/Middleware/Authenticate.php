<?php

namespace AgenterLab\AGWS\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Auth\GenericUser;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $clientToken = $request->headers->get(
            app('config')->get('agws.token_name')
        );

        if ($clientToken) {
            $this->validateToken($clientToken);
        }
       
        return $next($request);
    }

    /**
     * Validate client token
     */
    private function validateToken(string $clientToken) {

        list($kid, $jwt) = array_pad(explode(':', $clientToken, 2), 2, null);

        $publicKeyPath = app('config')->get('agws.public_key_path');

        if (!$publicKeyPath) {
            throw new \InvalidArgumentException('Public key not set');
        }
        
        if (!is_file($publicKeyPath)) {
            throw new \InvalidArgumentException('Invalid key');
        }

        $publicKey = file_get_contents($publicKeyPath);

        $decoded = JWT::decode($jwt, new Key($publicKey, 'RS256'));

        if (!empty($decoded->sub)) {
            $this->auth->setUser(new GenericUser(['id' => $decoded->sub]));
        }
        if (!empty($decoded->cid)) {
            $this->auth->setCompany($decoded->cid);
        }
    }
}
