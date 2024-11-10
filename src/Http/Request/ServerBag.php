<?php declare(strict_types=1);

namespace Imhotep\Http\Request;

class ServerBag extends ParameterBug
{
    public function getHeaders(): array
    {
        $headers = [];

        foreach ($this->parameters as $key => $val) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $val;
            }
            elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $headers[$key] = $val;
            }
        }

        if (isset($this->parameters['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $this->parameters['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = $this->parameters['PHP_AUTH_PW'] ?? '';
        }
        else {
            $auth = null;
            if (isset($this->parameters['HTTP_AUTHORIZATION'])) {
                $auth = $this->parameters['HTTP_AUTHORIZATION'];
            } elseif (isset($this->parameters['REDIRECT_HTTP_AUTHORIZATION'])) {
                $auth = $this->parameters['REDIRECT_HTTP_AUTHORIZATION'];
            }

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
            } elseif (isset($headers['PHP_AUTH_DIGEST'])) {
                $headers['AUTHORIZATION'] = $headers['PHP_AUTH_DIGEST'];
            }
        }

        return $headers;
    }

    protected function modifyKey(string $key): string
    {
        return str_replace('-', '_', strtoupper($key));
    }
}