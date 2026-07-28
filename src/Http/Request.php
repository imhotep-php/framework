<?php declare(strict_types=1);

namespace Imhotep\Http;

use Closure;
use Imhotep\Contracts\Http\Request as RequestContract;
use Imhotep\Contracts\Routing\Route;
use Imhotep\Contracts\Session\ISession;
use Imhotep\Contracts\Validation\IValidator;
use Imhotep\Http\Traits\HasHeaders;
use Imhotep\Support\Arr;
use Imhotep\Support\Str;
use Imhotep\Support\Traits\DeprecatedGetters;
use Imhotep\Support\Traits\Macroable;

/**
 * @method IValidator validate(array $rules, array $messages = [], array $attributes = [])
 */
class Request implements RequestContract
{
    use HasHeaders, DeprecatedGetters, Macroable {
        __call as macroCall;
    }

    public ParameterBag $query;

    public ParameterBag $post;

    public ParameterBag $json;

    public ParameterBag $cookies;

    public FileBag $files;

    public ServerBag $server;

    protected ?Closure $routeResolver = null;

    protected ?Closure $userResolver = null;

    protected ?string $content;


    public static function createFromGlobals(): static
    {
        return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }

    public static function create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], ?string $content = null): static
    {
        $server = array_replace([
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Imhotep',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
        ], $server);

        $server['PATH_INFO'] = '';
        $server['REQUEST_METHOD'] = strtoupper($method);

        $components = parse_url($uri);

        if (isset($components['host'])) {
            $server['SERVER_NAME'] = $components['host'];
            $server['HTTP_HOST'] = $components['host'];
        }

        if (isset($components['scheme'])) {
            if ('https' === $components['scheme']) {
                $server['HTTPS'] = 'on';
                $server['SERVER_PORT'] = 443;
            } else {
                unset($server['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }

        if (isset($components['port'])) {
            $server['SERVER_PORT'] = $components['port'];
            $server['HTTP_HOST'] .= ':'.$components['port'];
        }

        if (isset($components['user'])) {
            $server['PHP_AUTH_USER'] = $components['user'];
        }

        if (isset($components['pass'])) {
            $server['PHP_AUTH_PW'] = $components['pass'];
        }

        if (!isset($components['path'])) {
            $components['path'] = '/';
        }
        elseif (!str_starts_with($components['path'], '/')){
            $components['path'] = '/'.$components['path'];
        }

        switch (strtoupper($method)) {
            case 'POST':
            case 'PUT':
            case 'DELETE':
                if (!isset($server['CONTENT_TYPE'])) {
                    $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
                }
                // no break
            case 'PATCH':
                $post = $parameters;
                $query = [];
                break;
            default:
                $post = [];
                $query = $parameters;
                break;
        }

        $queryString = '';
        if (isset($components['query'])) {
            parse_str(html_entity_decode($components['query']), $qs);

            if ($query) {
                $query = array_replace($qs, $query);
                $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
            } else {
                $query = $qs;
                $queryString = $components['query'];
            }
        } elseif ($query) {
            $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $server['REQUEST_URI'] = $components['path'].('' !== $queryString ? '?'.$queryString : '');
        $server['QUERY_STRING'] = $queryString;

        return new static($query, $post, $cookies, $files, $server, $content);
    }

    public function __construct(array $query = [], array $post = [], array $cookies = [], array $files = [], array $server = [], ?string $content = null)
    {
        $this->query = new ParameterBag($query);
        $this->post = new ParameterBag($post);
        $this->cookies = new ParameterBag($cookies);
        $this->server = new ServerBag($server);
        $this->headers = new HeaderBag($this->server->getHeaders());
        $this->files = new FileBag($files);
        $this->content = $content;

        $this->makeJson();
    }

    protected function makeJson(): void
    {
        $this->json = new ParameterBag([]);

        if (($content = $this->content()) && !empty($content)) {
            $json = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return;
            }

            $this->json->replace($json);
        }
    }


    public function method(): string
    {
        return $this->server->get('REQUEST_METHOD', 'GET');
    }

    public function isMethod(string|array $methods): bool
    {
        $methods = is_array($methods) ? $methods : func_get_args();

        foreach ($methods as $method) {
            if ($this->method() === strtoupper($method)) {
                return true;
            }
        }

        return false;
    }

    public function secure(): bool
    {
        $https = $this->server->get('HTTPS');
        $port = $this->server->get('SERVER_PORT');

        if ($https == 'on' || $https == 1 || $port == 443) {
            return true;
        }

        if ($this->server->get('HTTP_X_FORWARDED_PROTO') === 'https') {
            return true;
        }

        if ($this->server->get('HTTP_X_FORWARDED_SSL') === 'on') {
            return true;
        }

        return false;
    }

    public function scheme(): string
    {
        return $this->secure() ? 'https' : 'http';
    }

    public function host(bool $withPort = false): string
    {
        if (! $host = $this->headers->get('HOST')) {
            $host = $this->server->get('SERVER_NAME') ?? $this->server->get('SERVER_ADDR');
        }

        // Remove port from host
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

        if (! $withPort) {
            return $host;
        }

        $scheme = $this->scheme();
        $port = $this->port();

        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            return $host;
        }

        return $host.':'.$port;
    }

    public function port(): int
    {
        if ($host = $this->headers->get('HOST')) {
            if (preg_match('/:(\d+)/', $host, $match)) {
                return intval($match[1]);
            }
        }

        if ($port = $this->server->get('SERVER_PORT')) {
            $port = intval($port);

            if ($port > 0) {
                return $port;
            }
        }

        return $this->scheme() === 'https' ? 443 : 80;
    }

    public function path(): string
    {
        $path = $this->server->get('REQUEST_URI', '');

        if (false !== $pos = strpos($path, '?')) {
            $path = substr($path, 0, $pos);
        }

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = substr($path, 0, -1);
        }

        return $path;
    }

    public function queryString(): string
    {
        return $this->server->get('QUERY_STRING', '');
    }

    public function content(): string
    {
        if (is_null($this->content)) {
            $content = file_get_contents('php://input');
            $this->content = is_string($content) ? $content : '';
        }

        return $this->content;
    }

    public function root(): string
    {
        return $this->scheme().'://'.$this->host(true);
    }

    public function uri(): string
    {
        return $this->server->get('REQUEST_URI', '');
    }

    public function url(array $query = []): string
    {
        $query = http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        $path = $this->path();
        $url = rtrim($this->scheme().'://'.$this->host(true).$path, '/');
        $question = ($path === '/') ? '/?' : '?';

        return empty($query) ? $url : $url.$question.$query;
    }

    public function fullUrl(array $query = [], array $without = []): string
    {
        return $this->url(Arr::except(array_merge($this->query(), $query), $without));
    }


    public function server(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->server->all();
        }

        return $this->server->get($key, $default);
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->query->all();
        }

        return $this->query->get($key, $default);
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->post->all();
        }

        return $this->post->get($key, $default);
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->json->all();
        }

        return $this->json->get($key, $default);
    }

    public function cookies(string|array|null $keys = null): array
    {
        if (is_null($keys)) {
            return $this->cookies->all();
        }

        return $this->cookies->only(is_array($keys) ? $keys : func_get_args());
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies->get($key, $default);
    }

    public function files(string|array|null $keys = null): array
    {
        if (is_null($keys)) {
            return $this->files->all();
        }

        return $this->files->only(is_array($keys) ? $keys : func_get_args());
    }

    public function file(string $key, mixed $default = null): mixed
    {
        return $this->files->get($key, $default);
    }

    public function hasFile(string $key): bool
    {
        return $this->files->has($key);
    }


    public function all(): array
    {
        return array_merge($this->query(), $this->post(), $this->json(), $this->files());
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $input = $this->all();

        if (is_null($key)) {
            return $input;
        }

        return Arr::get($input, $key, $default);
    }

    public function only(string|array $keys): array
    {
        $result = [];

        $input = $this->all();

        $keys = is_array($keys) ? $keys : func_get_args();

        $default = new \stdClass();

        foreach ($keys as $key) {
            $value = Arr::data($input, $key, $default);

            if ($value !== $default) {
                Arr::set($result, $key, $value);
            }
        }

        return $result;
    }

    public function except(string|array $keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $result = $this->all();

        Arr::forget($result, $keys);

        return $result;
    }

    public function has(string|array $keys): bool
    {
        $input = $this->all();

        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if (! Arr::has($input, $key)) {
                return false;
            }
        }

        return true;
    }

    public function hasAny(string|array $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        return Arr::hasAny($this->all(), $keys);
    }

    public function whenHas(string $key, callable $callback, ?callable $default = null): static
    {
        if ($this->has($key)) {
            $callback(Arr::get($this->all(), $key));
        }

        if ($default) {
            $default();
        }

        return $this;
    }

    public function filled(string|array $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if (Str::isEmpty(Arr::get($this->all(), $key))) {
                return false;
            }
        }

        return true;
    }

    public function notFilled(string|array $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if (! Str::isEmpty(Arr::get($this->all(), $key))) {
                return false;
            }
        }

        return true;
    }

    public function anyFilled(string|array $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if (! Str::isEmpty(Arr::get($this->all(), $key))) {
                return true;
            }
        }

        return false;
    }

    public function whenFilled(string $key, callable $callback, ?callable $default = null): static
    {
        $value = Arr::get($this->all(), $key);

        if (! Str::isEmpty($value)) {
            $callback($value);
        }

        if ($default) {
            $default();
        }

        return $this;
    }

    public function missing(string|array $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        return Arr::missing($this->all(), $keys);

        //return ! $this->has($keys);
    }

    public function whenMissing(string $key, callable $callback, ?callable $default = null): static
    {
        if (Arr::missing($this->all(), $key)) {
            $callback();
        }

        if ($default) {
            $default();
        }

        return $this;
    }

    // Input types
    public function string(string $key, string|callable|null $default = ''): ?string
    {
        $value = $this->input($key);

        if (is_null($value) || is_array($value)) {
            return value($default);
        }

        return trim(strval($value));
    }

    public function str(string $key, string|callable|null $default = ''): ?string
    {
        return $this->string($key, $default);
    }

    public function integer(string $key, int|callable|null $default = 0): ?int
    {
        $value = filter_var($this->input($key), FILTER_VALIDATE_INT);

        return is_int($value) ? $value : value($default);
    }

    public function int(string $key, int|callable|null $default = 0): ?int
    {
        return $this->integer($key, $default);
    }

    public function float(string $key, float|callable|null $default = 0.0): ?float
    {
        $value = filter_var($this->input($key), FILTER_VALIDATE_FLOAT);

        return is_float($value) ? $value : value($default);
    }

    public function boolean(string $key, bool|callable|null $default = false): ?bool
    {
        $value = $this->input($key);

        if (is_null($value)) {
            return value($default);
        }

        if (is_bool($value)) {
            return $value;
        }

        $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return is_null($value) ? value($default) : $value;
    }

    public function bool(string $key, bool|callable|null $default = false): ?bool
    {
        return $this->boolean($key, $default);
    }


    // Work with headers
    public function ip(): string
    {
        if ($this->server->has('HTTP_X_FORWARDED_FOR')) {
            $value = explode(',', $this->server->get('HTTP_X_FORWARDED_FOR'));

            return trim($value[0]);
        }

        // For Cloudflare
        if ($this->server->has('HTTP_CF_CONNECTING_IP')) {
            return $this->server->get('HTTP_CF_CONNECTING_IP');
        }

        return $this->server->get('REMOTE_ADDR');
    }

    public function userAgent(): string
    {
        return $this->headers->get('User-Agent', '');
    }

    public function bearerToken(): ?string
    {
        $token = $this->headers->get('Authorization', '');

        $pos = stripos($token, 'Bearer');

        if ($pos !== false) {
            $token = substr($token, $pos + 7);

            if (str_contains($token, ',')) {
                return trim(strstr($token, ',', true));
            }

            return trim($token);
        }

        return null;
    }

    public function getUser(): ?string
    {
        return $this->headers->get('PHP_AUTH_USER');
    }

    public function getPassword(): ?string
    {
        return $this->headers->get('PHP_AUTH_PW');
    }

    public function ajax(): bool
    {
        return $this->headers->get('X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    public function pajax(): bool
    {
        return $this->headers->get('X_PJAX') === 'true';
    }

    public function prefetch(): bool
    {
        $moz = $this->headers->get('X_MOZ', '');
        $purpose = $this->headers->get('X_PURPOSE', '');

        return strcasecmp($moz, 'prefetch') === 0 || strcasecmp($purpose, 'preview') === 0;
    }


    // Work with Accept
    protected ?array $acceptsCache = null;

    protected ?array $acceptLanguages = null;

    public function getAcceptableTypes(): array
    {
        if ($this->acceptsCache !== null) {
            return $this->acceptsCache;
        }

        $accepts = trim($this->headers->get('Accept', ''));

        if ($accepts !== '') {
            $accepts = array_map(function($accept) {
                if ($pos = stripos($accept, ';')) {
                    $accept = substr($accept, 0, $pos);
                }

                return strtolower(trim($accept));
            }, explode(',', $accepts));
        }

        return $this->acceptsCache = is_array($accepts) ? $accepts : [];
    }

    public function accepts(string|array $contentTypes): bool
    {
        // TODO: Check for optimization

        $accepts = $this->getAcceptableTypes();

        if (count($accepts) === 0) {
            return true;
        }

        $contentTypes = is_array($contentTypes) ? $contentTypes : func_get_args();

        foreach ($accepts as $accept) {
            if ($accept === '*/*' || $accept === '*') {
                return true;
            }

            foreach ($contentTypes as $type) {
                $type = strtolower($type);

                if ($accept === $type) {
                    return true;
                }

                if ($accept === strtok($type, '/').'/*') {
                    return true;
                }

                $split = explode("/", $accept);

                if (isset($split[1])) {
                    $split[0] = preg_quote($split[0], '#');
                    $split[1] = preg_quote($split[1], '#');

                    if (preg_match("#{$split[0]}/.+\+{$split[1]}#", $type)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function acceptsAny(): bool
    {
        $accepts = $this->getAcceptableTypes();

        return count($accepts) === 0 || in_array($accepts[0] ?? '', ['*/*','*']);
    }

    public function acceptsJson(): bool
    {
        return $this->accepts('application/json');
    }

    public function acceptsHtml(): bool
    {
        return $this->accepts('text/html');
    }

    public function format(string $default = 'html'): string
    {
        // TODO: Add custom formats

        $defaultFormats = [
            'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
            'html' => ['text/html', 'application/xhtml+xml'],
            'txt' => ['text/plain'],
            'js' => ['application/javascript', 'application/x-javascript', 'text/javascript'],
            'css' => ['text/css'],
            'json' => ['application/json', 'application/x-json'],
            'jsonld' => ['application/ld+json'],
            'rdf' => ['application/rdf+xml'],
            'atom' => ['application/atom+xml'],
            'rss' => ['application/rss+xml'],
            'form' => ['application/x-www-form-urlencoded', 'multipart/form-data'],
        ];

        foreach ($defaultFormats as $format => $contentTypes) {
            if ($this->accepts($contentTypes)) {
                return $format;
            }
        }

        return $default;
    }

    public function expectsJson(): bool
    {
        return ($this->ajax() && ! $this->pajax() && $this->acceptsAny()) || $this->wantsJson();
    }

    public function wantsJson(): bool
    {
        $accepts = $this->getAcceptableTypes();

        return isset($accepts[0]) && (str_contains($accepts[0], '/json') || str_contains($accepts[0], '+json'));
    }

    public function getAcceptedLanguages(string|array|null $languages = null): array
    {
        if (is_null($this->acceptLanguages)) {
            $this->acceptLanguages = [];

            $pattern = '/([\w\-_]+)\s*(;\s*q\s*=\s*(\d*\.\d*))?/';
            $accept = $this->headers->get('accept-language');

            if (!is_null($accept) && ($n = preg_match_all($pattern, $accept, $matches)) > 0) {
                for ($i = 0; $i < $n; ++$i) {
                    $lang = strtolower(str_replace('-', '_', $matches[1][$i]));

                    $this->acceptLanguages[$lang] = empty($matches[3][$i])
                        ? 1.0
                        : floatval($matches[3][$i]);
                }

                arsort($this->acceptLanguages);

                $this->acceptLanguages = array_keys($this->acceptLanguages);
            }
        }

        if (! is_null($languages)) {
            if (is_string($languages)) $languages = [$languages];

            foreach ($languages as $key => $val) {
                $languages[$key] = strtolower(str_replace('-', '_', $val));
            }

            return array_values(array_intersect($this->getAcceptedLanguages(), $languages));
        }

        return $this->acceptLanguages;
    }

    public function acceptLanguage(string $language): bool
    {
        $language = strtolower(str_replace('-', '_', $language));

        return in_array($language, $this->getAcceptedLanguages());
    }


    // Work with routes
    public function getRouteResolver(): Closure
    {
        return $this->routeResolver ?? function () {  return null; };
    }

    public function setRouteResolver(Closure $resolver): static
    {
        $this->routeResolver = $resolver;

        return $this;
    }

    public function route(): ?Route
    {
        return call_user_func($this->getRouteResolver());
    }


    // Auth user
    public function setUserResolver(Closure $resolver): static
    {
        $this->userResolver = $resolver;

        return $this;
    }

    public function getUserResolver(): Closure
    {
        return $this->userResolver ?: function () { };
    }

    public function user(?string $guard = null): mixed
    {
        return call_user_func($this->getUserResolver(), $guard);
    }


    // Session
    protected ISession $session;

    public function setSession(ISession $session): void
    {
        $this->session = $session;
    }

    public function hasSession(): bool
    {
        return ! is_null($this->session);
    }

    public function getSession(): ISession
    {
        if (! $this->hasSession()) {
            throw new \RuntimeException('Session store not set on request.');
        }

        return $this->session;
    }

    public function session(): ISession
    {
        return $this->getSession();
    }

    public function old(string $key, mixed $default = null): mixed
    {
        return $this->hasSession() ? $this->session->getOldInput($key, $default) : value($default);
    }

    public function flash(): void
    {
        $this->session->setOldInput($this->all());
    }

    public function flashOnly(string|array $keys): void
    {
        $this->session->setOldInput(
            $this->only(is_array($keys) ? $keys : func_get_args())
        );
    }

    public function flashExcept(string|array $keys): void
    {
        $this->session->setOldInput(
            $this->except(is_array($keys) ? $keys : func_get_args())
        );
    }

    public function flush(): void
    {
        $this->session->setOldInput([]);
    }


    public function offsetExists(mixed $offset): bool
    {
        return Arr::has($this->all(), $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->input($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (in_array($this->method(), ['GET', 'HEAD'])) {
            $this->query->set($offset, $value);
        }
        else {
            $this->post->set($offset, $value);
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if (in_array($this->method(), ['GET', 'HEAD'])) {
            $this->query->remove($offset);
        }
        else {
            $this->post->remove($offset);
        }
    }

    public function __get(string $key): mixed
    {
        return $this->input($key);
    }

    public function __set(string $key, mixed $value): void
    {
        if (in_array($this->method(), ['GET', 'HEAD'])) {
            $this->query->set($key, $value);
        }
        else {
            $this->post->set($key, $value);
        }
    }

    public function __call(string $method, array $parameters): mixed
    {
        if ($result = $this->deprecatedGettersCall($method, $parameters)) {
            return $result;
        }

        return $this->macroCall($method, $parameters);
    }
}