<?php

declare(strict_types=1);

namespace Imhotep\Http;

use Imhotep\Contracts\Http\Request as RequestContract;
use Imhotep\Contracts\Http\Response as ResponseContract;
use Imhotep\Cookie\Cookie;

class Response implements ResponseContract
{
    protected string $version = '1.1';

    protected string $charset = 'utf-8';

    protected array $cookies = [];

    protected array $headers = [];

    protected int $statusCode = 200;

    protected string $statusText = '';

    protected ?string $content = null;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->setContent($content)
             ->setStatusCode($statusCode)
             ->setHeaders($headers);
    }

    public function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function code(int $statusCode): static
    {
        return $this->setStatusCode($statusCode);
    }

    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function header(string $name, string $value): static
    {
        return $this->setHeader($name, $value);
    }

    public function setHeaders(array $headers = []): static
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function withHeaders(array $headers = []): static
    {
        return $this->setHeaders($headers);
    }

    public function headers(array $headers = []): static
    {
        return $this->setHeaders($headers);
    }

    public function clearCookies(): void
    {
        $this->cookies = [];
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function setCookie(Cookie|string $cookie, string $value = '', int $minutes = 0, string $path = '', string $domain = '', bool $secure = false, bool $httpOnly = false, string $sameSite = null): static
    {
        if (is_string($cookie)) {
            $expires = ($minutes === 0) ? 0 : time() + ($minutes * 60);

            $cookie = new Cookie($cookie, $value, $expires, $path, $domain, $secure, $httpOnly, $sameSite);
        }

        $this->cookies[] = $cookie;

        return $this;
    }

    public function cookie(Cookie|string $cookie, string $value = '', int $minutes = 0, string $path = '', string $domain = '', bool $secure = false, bool $httpOnly = false, string $sameSite = null): static
    {
        return $this->setCookie($cookie, $value, $minutes, $path, $domain, $secure, $httpOnly, $sameSite);
    }

    public function withoutCookie(Cookie|string $cookie, $path = null, $domain = null): static
    {
        if (is_string($cookie)) {
            $cookie = new Cookie($cookie, '', -2628000, $path, $domain);
        }

        return $this->setCookie($cookie);
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content ?? '';
    }

    public function content(mixed $content = null): static|string|null
    {
        if (is_null($content)) {
            return $this->getContent();
        }

        return $this->setContent($content);
    }

    public function json(array $data): static
    {
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE);

        $this->header('Content-Type', 'application/json');

        return $this;
    }

    public function prepare(RequestContract $request): static
    {
        if ($request->isMethod('HEAD')) {
            $this->setContent(null);
        }

        return $this;
    }

    public function send(): void
    {
        $this->sendHeaders()->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        } elseif (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            static::closeOutputBuffers(0, true);
        }
    }

    protected function sendHeaders(): static
    {
        // headers have already been sent by the developer
        if (headers_sent()) {
            return $this;
        }

        // headers
        foreach($this->headers as $name => $value){
            if (strtolower($name) == 'content-type') {
                header($name.': '.$value.'; charset='.$this->charset, false, $this->statusCode);
            }
            else{
                header($name.': '.$value, false, $this->statusCode);
            }
        }

        foreach ($this->cookies as $cookie) {
            header("Set-Cookie: ".(string)$cookie, false, $this->statusCode);
        }

        header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText), true, $this->statusCode);

        return $this;
    }

    protected function sendContent(): static
    {
        echo $this->getContent();

        return $this;
    }

    public static function closeOutputBuffers(int $targetLevel, bool $flush): void
    {
        $status = ob_get_status(true);
        $level = \count($status);
        $flags = \PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? \PHP_OUTPUT_HANDLER_FLUSHABLE : \PHP_OUTPUT_HANDLER_CLEANABLE);

        while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])) {
            if ($flush) {
                ob_end_flush();
                flush();
            } else {
                ob_end_clean();
            }
        }
    }

    public function isRedirect(string $location = null): bool
    {
        return in_array($this->statusCode, [201, 301, 302, 303, 307, 308])
                && (null === $location || $location == ($this->headers['Location'] ?? null));
    }
}