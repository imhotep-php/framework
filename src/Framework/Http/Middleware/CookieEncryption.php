<?php

namespace Imhotep\Framework\Http\Middleware;

use Imhotep\Contracts\Encryption\Encrypter;
use Imhotep\Contracts\Encryption\EncryptionException;
use Imhotep\Contracts\Http\Request;
use Imhotep\Contracts\Http\Response;

class CookieEncryption
{
    protected array $except = [];

    public function __construct(
        protected Encrypter $encrypter
    ) { }

    public function handle(Request $request, \Closure $next)
    {
        return $this->encrypt($next($this->decrypt($request)));
    }

    protected function encrypt(Response $response): Response
    {
        $cookies = $response->getCookies();

        $response->clearCookies();

        foreach ($cookies as $cookie) {
            if ($this->isDisabled($cookie->getName())) continue;

            $cookie->setValue( $this->encrypter->encryptString($cookie->getValue()), false );

            $response->setCookie($cookie);
        }

        return $response;
    }

    protected function decrypt(Request $request): Request
    {
        $cookies = $request->cookies->all();

        foreach ($cookies as $key => $val) {
            if ($this->isDisabled($key)) continue;

            try {
                $val = $this->encrypter->decryptString($val);

                $request->cookies->set($key, $val);
            }
            catch (EncryptionException $e) {
                $request->cookies->set($key, null);
            }
        }

        return $request;
    }

    protected function isDisabled(string $name): bool
    {
        return in_array($name, $this->except);
    }
}