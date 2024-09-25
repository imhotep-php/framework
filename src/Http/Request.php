<?php declare(strict_types=1);

namespace Imhotep\Http;

use Closure;
use Imhotep\Contracts\Http\Request as RequestContract;
use Imhotep\Contracts\Routing\Route;
use Imhotep\Contracts\Session\Session;
use Imhotep\Contracts\Validation\Validator;
use Imhotep\Http\Request\HeaderBag;
use Imhotep\Http\Request\ParameterBug;
use Imhotep\Support\Arr;
use Imhotep\Support\Str;
use Imhotep\Support\Traits\Macroable;

/**
 * @method Validator validate(array $rules, array $messages = [])
 */
class Request implements \ArrayAccess, RequestContract
{
    use Macroable;

    //protected array $attributes;

    public ParameterBug $query;

    public ParameterBug $post;

    public ParameterBug $json;

    public ParameterBug $cookies;

    public ParameterBug $files;

    public ParameterBug $server;

    public HeaderBag $headers;

    protected ?Route $route = null;

    protected ?Closure $userResolver = null;

    public mixed $content;

    public static function createFromGlobals(): static
    {
        return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }

    public static function create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = []): static
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
                $queryString = http_build_query($query, '', '&');
            } else {
                $query = $qs;
                $queryString = $components['query'];
            }
        } elseif ($query) {
            $queryString = http_build_query($query, '', '&');
        }

        $server['REQUEST_URI'] = $components['path'].('' !== $queryString ? '?'.$queryString : '');
        $server['QUERY_STRING'] = $queryString;

        $request = new static($query, $post, $cookies, $files, $server);

        return $request;
    }

    public function __construct(array $query = [], array $post = [], array $cookies = [], array $files = [], array $server = [])
    {
        $this->query = new ParameterBug($query);
        $this->post = new ParameterBug($post);
        $this->cookies = new ParameterBug($cookies);
        $this->server = new ParameterBug($server);

        $this->makeHeaders();
        $this->makeFiles($files);
        $this->makeJson();
    }

    protected function makeHeaders(): void
    {
        $this->headers = new HeaderBag();

        foreach ($this->server->all() as $key => $val) {
            if (str_starts_with($key, 'HTTP_')) {
                $this->headers->set(substr($key, 5), $val);
            }
            elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $this->headers->set($key, $val);
            }
        }

        if ($this->server->has('PHP_AUTH_USER')) {
            $this->headers->set('PHP_AUTH_USER', $this->server->get('PHP_AUTH_USER'));
            $this->headers->set('PHP_AUTH_PW', $this->server->get('PHP_AUTH_PW', ''));
        }

        if ($this->headers->has('PHP_AUTH_USER')) {
            $this->headers->set(
                'AUTHORIZATION',
                'Basic '.base64_encode($this->headers->get('PHP_AUTH_USER').':'.$this->headers->get('PHP_AUTH_PW', ''))
            );
        }
        elseif ($this->headers->has('PHP_AUTH_DIGEST')) {
            $this->headers->set('AUTHORIZATION', $this->headers->get('PHP_AUTH_DIGEST'));
        }
    }

    protected function makeFiles(array $files): void
    {
        $this->files = new ParameterBug();

        foreach ($files as $key => $file) {
            if (! is_array($file['tmp_name'])) {
                if ($uploadedFile = UploadedFile::createFrom($file)) {
                    $this->files->set($key, $uploadedFile);
                }
                continue;
            }

            $uploadedFiles = [];
            for ($i = 0; $i < count($file['tmp_name']); $i++) {
                $uploadedFile = UploadedFile::createFrom([
                    'tmp_name' => $file['tmp_name'][$i] ?? '',
                    'name' => $file['name'][$i] ?? '',
                    'type' => $file['type'][$i] ?? '',
                    'size' => $file['size'][$i] ?? 0,
                    'error' => $file['error'][$i] ?? -1
                ]);

                if ($uploadedFile) $uploadedFiles[] = $uploadedFile;
            }

            if (count($uploadedFiles) > 0) {
                $this->files->set($key, $uploadedFiles);
            }
        }
    }

    protected function makeJson(): void
    {
        $json = json_decode($this->getContent(), true);

        if (is_null($json) || json_last_error() !== JSON_ERROR_NONE) {
            $json = [];
        }

        $this->json = new ParameterBug($json);
    }

    public function getMethod(): string
    {
        return $this->server->get('REQUEST_METHOD', 'GET');
    }

    public function method(): string
    {
        return $this->getMethod();
    }

    public function isMethod(string|array $methods): bool
    {
        if (! is_array($methods)) $methods = func_get_args();

        foreach ($methods as $method) {
            if ($this->getMethod() === strtoupper($method)) {
                return true;
            }
        }

        return false;
    }


    public function isSecure(): bool
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

    public function secure(): bool
    {
        return $this->isSecure();
    }


    public function getScheme(): string
    {
        return $this->secure() ? 'https' : 'http';
    }

    public function scheme(): string
    {
        return $this->getScheme();
    }


    public function getHost(bool $withPort = false): string
    {
        $host = $this->server->get('SERVER_NAME') ?? $this->server->get('SERVER_ADDR');

        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));

        if (! $withPort) {
            return $host;
        }

        $scheme = $this->getScheme();
        $port = $this->getPort();

        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            return $host;
        }

        return $host.':'.$port;
    }

    public function host(bool $withPort = false): string
    {
        return $this->getHost($withPort);
    }


    public function getPort(): int
    {
        if ($port = $this->server->get('SERVER_PORT')) {
            return intval($port);
        }

        return $this->getScheme() === 'https' ? 443 : 80;
    }

    public function port(): int
    {
        return $this->getPort();
    }


    public function getPath(): string
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

    public function path(): string
    {
        return $this->getPath();
    }


    public function getQueryString(): string
    {
        return $this->fixQueryString($this->server->get('QUERY_STRING'));
    }

    public function queryString(): string
    {
        return $this->getQueryString();
    }

    protected function fixQueryString(?string $qs): string
    {
        if (($qs ?? '') === '') {
            return '';
        }

        return $qs;
    }


    public function getContent(): mixed
    {
        if (! isset($this->content)) {
            $this->content = file_get_contents('php://input');
        }

        return $this->content;
    }


    public function root(): string
    {
        return $this->scheme().'://'.$this->host(true);
    }

    public function url(bool|array $withQuery = false): string
    {
        $query = '';
        if (is_array($withQuery) && count($withQuery) > 0) {
            $query = array_merge($this->query(), $withQuery);
            $query = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }
        elseif ($withQuery === true) {
            $query = $this->queryString();
        }

        $path = $this->path();
        $url = rtrim($this->scheme().'://'.$this->host(true).$path, '/');
        $question = ($path === '/') ? '/?' : '?';

        return empty($query) ? $url : $url.$question.$query;
    }

    public function fullUrl(): string
    {
        return $this->url(true);
    }

    public function fullUrlWithQuery(array $query): string
    {
        return $this->url($query);
    }


    public function getUri(){
        return $this->server->get('REQUEST_URI', '');
    }

    public function uri()
    {
        return $this->getUri();
    }

    public function server(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->server->all();
        }

        return $this->server->get($key, $default);
    }

    public function headers(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->headers->all();
        }

        return $this->headers->get($key, $default);
    }

    public function header(string $key, mixed $default = null): mixed
    {
        return $this->headers->get($key, $default);
    }

    public function hasHeader(string|array $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if (! $this->headers->has($key)) {
                return false;
            }
        }

        return true;
    }


    public function cookies(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->cookies->all();
        }

        return $this->cookies->get($key, $default);
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies->get($key, $default);
    }


    public function query(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->query->all();
        }

        return $this->query->get($key, $default);
    }

    public function post(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->post->all();
        }

        return $this->post->get($key, $default);
    }

    public function json(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->json->all();
        }

        return $this->json->get($key, $default);
    }

    public function files(string $key = null, mixed $default = null): mixed
    {
        if (is_null($key)) {
            return $this->files->all();
        }

        return $this->files->get($key, $default);
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

    public function input(string $key = null, mixed $default = null): mixed
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

    public function whenHas(string $key, callable $callback, callable $default = null): static
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

    public function whenFilled(string $key, callable $callback, callable $default = null): static
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

        return ! $this->has($keys);
    }

    public function whenMissing(string $key, callable $callback, callable $default = null): static
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
    public function string(string $key, ?string $default = ''): ?string
    {
        $value = $this->input($key, $default);

        if (is_array($value) || is_bool($value)) {
            return $default;
        }

        return trim(strval($value));
    }

    public function str(string $key, ?string $default = ''): ?string
    {
        return $this->string($key, $default);
    }

    public function integer(string $key, ?int $default = 0): ?int
    {
        $value = filter_var($this->input($key, $default), FILTER_VALIDATE_INT);

        return is_int($value) ? $value : $default;
    }

    public function int(string $key, ?int $default = 0): ?int
    {
        return $this->integer($key, $default);
    }

    public function float(string $key, ?float $default = 0.0): ?float
    {
        $value = filter_var($this->input($key, $default), FILTER_VALIDATE_FLOAT);

        return is_float($value) ? $value : $default;
    }

    public function boolean(string $key, ?bool $default = false): ?bool
    {
        return filter_var($this->input($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    public function bool(string $key, ?bool $default = false): ?bool
    {
        return $this->boolean($key, $default);
    }


    // Work with headers
    public function ip(): string
    {
        if ($this->server->has('HTTP_X_FORWARDED_FOR')) {
            return $this->server->get('HTTP_X_FORWARDED_FOR');
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
        if (is_null($this->acceptsCache)) {
            $accepts = trim($this->headers->get('Accept', ''));

            if ($accepts === '') {
                $accepts = [];
            }
            else {
                if (str_contains($accepts, ';')) {
                    $accepts = strtok($accepts, ';');
                }

                $accepts = array_map(fn($val) => strtolower(trim($val)), explode(",", $accepts));
            }

            $this->acceptsCache = $accepts;
        }

        return $this->acceptsCache;
    }

    public function accepts(string|array $contentTypes): bool
    {
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
        $defaultFormats = [
            'html' => ['text/html', 'application/xhtml+xml'],
            'txt' => ['text/plain'],
            'js' => ['application/javascript', 'application/x-javascript', 'text/javascript'],
            'css' => ['text/css'],
            'json' => ['application/json', 'application/x-json'],
            'jsonld' => ['application/ld+json'],
            'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
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

    public function getAcceptedLanguages(string|array $languages = null): array
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
    public function getRoute(): ?Route
    {
        return $this->route ?? null;
    }

    public function setRoute(Route $route): static
    {
        $this->route = $route;

        return $this;
    }

    public function route(Route $route = null): static|Route|null
    {
        if (is_null($route)) {
            return $this->getRoute();
        }

        return $this->setRoute($route);
    }


    public function setUserResolver(Closure $resolver): static
    {
        $this->userResolver = $resolver;

        return $this;
    }

    public function getUserResolver(): Closure
    {
        return $this->userResolver ?: function () { };
    }

    public function user(string $guard = null): mixed
    {
        return call_user_func($this->getUserResolver(), $guard);
    }

    // Session

    protected Session $session;

    public function setSession(Session $session): void
    {
        $this->session = $session;
    }

    public function hasSession(): bool
    {
        return ! is_null($this->session);
    }

    public function getSession(): Session
    {
        if (! $this->hasSession()) {
            throw new \RuntimeException('Session store not set on request.');
        }

        return $this->session;
    }

    public function session(): Session
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
        return $this->all()[$offset] ?? false;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->input($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (in_array($this->getMethod(), ['GET', 'HEAD'])) {
            $this->query->set($offset, $value);
        }
        else {
            $this->post->set($offset, $value);
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if (in_array($this->getMethod(), ['GET', 'HEAD'])) {
            $this->query->remove($offset);
        }
        else {
            $this->post->remove($offset);
        }
    }

    public function __get(string $key)
    {
        return $this->input($key);
    }

    public function __set(string $key, mixed $value)
    {
        if (in_array($this->getMethod(), ['GET', 'HEAD'])) {
            $this->query->set($key, $value);
        }
        else {
            $this->post->set($key, $value);
        }
    }
}