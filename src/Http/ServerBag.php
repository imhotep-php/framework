<?php declare(strict_types=1);

namespace Imhotep\Http;

class ServerBag extends ParameterBag
{
    public function getHeaders(): array
    {
        //$headers = [];

        //$headers = array_filter($this->parameters, fn($key) => str_starts_with($key, 'HTTP_'), ARRAY_FILTER_USE_KEY);

        $keys = preg_grep('/^(HTTP_|CONTENT_TYPE|CONTENT_LENGTH|CONTENT_MD5)/', array_keys($this->parameters));
        $temp = array_intersect_key($this->parameters, array_flip($keys));

        $headers = [];
        foreach ($temp as $key => $value) {
            $key = preg_replace('/^(HTTP_)/', '', $key);
            $headers[$key] = $value;
        }

        /*
        $contentKeys = array_fill_keys(['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true);

        foreach ($this->parameters as $key => $val) {
            //if (str_starts_with($key, 'HTTP_')) {
            if (isset($key[4]) && $key[0] === 'H' && $key[4] === '_') {
                $headers[substr($key, 5)] = $val;
            }
            elseif (isset($contentKeys[$key])) {
                $headers[$key] = $val;
            }
        }
        */

        if (isset($this->parameters['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $this->parameters['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = $this->parameters['PHP_AUTH_PW'] ?? '';
        }
        else {
            $auth = $this->parameters['HTTP_AUTHORIZATION']
                ?? $this->parameters['REDIRECT_HTTP_AUTHORIZATION']
                ?? null;

            /*
            $auth = null;
            if (isset($this->parameters['HTTP_AUTHORIZATION'])) {
                $auth = $this->parameters['HTTP_AUTHORIZATION'];
            } elseif (isset($this->parameters['REDIRECT_HTTP_AUTHORIZATION'])) {
                $auth = $this->parameters['REDIRECT_HTTP_AUTHORIZATION'];
            }
            */

            if ($auth !== null) {
                if (str_starts_with(strtolower($auth), 'basic ')) {
                    $exploded = explode(':', base64_decode(substr($auth, 6)));
                    if (count($exploded) === 2) {
                        [$headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']] = $exploded;
                    }
                }
                elseif (str_starts_with(strtolower($auth), 'bearer ')) {
                    $headers['AUTHORIZATION'] = $auth;
                }
                elseif (str_starts_with(strtolower($auth), 'digest ') && empty($this->parameters['PHP_AUTH_DIGEST'])) {
                    $this->parameters['PHP_AUTH_DIGEST'] = $headers['PHP_AUTH_DIGEST'] = $auth;
                }
            }
        }

        if (! isset($headers['AUTHORIZATION'])) {
            if (isset($headers['PHP_AUTH_USER'])) {
                $headers['AUTHORIZATION'] = 'Basic ' . base64_encode($headers['PHP_AUTH_USER'] . ':' . ($headers['PHP_AUTH_PW'] ?? ''));
            }
            elseif (isset($headers['PHP_AUTH_DIGEST'])) {
                $headers['AUTHORIZATION'] = $headers['PHP_AUTH_DIGEST'];
            }
        }

        return $headers;
    }

    protected function modifyKey(mixed $key): mixed
    {
        return str_replace('-', '_', strtoupper($key));
    }
}