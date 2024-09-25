<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Http;

use Imhotep\Contracts\Routing\Route;
use Imhotep\Contracts\Session\Session;
use Imhotep\Contracts\Validation\Validator;
use Imhotep\Http\UploadedFile;

/**
 * @method array validate(array $rules, ...$params)
 */
interface Request
{
    public static function createFromGlobals(): static;

    public static function create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = []): static;

    public function getMethod(): string;

    public function method(): string;

    public function isMethod(string|array $methods): bool;

    public function isSecure(): bool;

    public function secure(): bool;

    public function getScheme(): string;

    public function scheme(): string;

    public function getHost(bool $withPort = false): string;

    public function host(bool $withPort = false): string;

    public function getPort(): int;

    public function port(): int;

    public function getPath(): string;

    public function path(): string;

    public function getQueryString(): string;

    public function queryString(): string;

    public function root(): string;

    public function url(bool|array $withQuery = false): string;

    public function fullUrl(): string;

    public function fullUrlWithQuery(array $query): string;

    public function getUri();

    public function uri();

    public function server(string $key = null, mixed $default = null): mixed;

    public function headers(string $key = null, mixed $default = null): mixed;

    public function header(string $key, mixed $default = null): mixed;

    public function hasHeader(string|array $keys): bool;

    public function cookies(string $key = null, mixed $default = null): mixed;

    public function cookie(string $key, mixed $default = null): mixed;

    public function query(string $key = null, mixed $default = null): mixed;

    public function post(string $key = null, mixed $default = null): mixed;

    public function json(string $key = null, mixed $default = null): mixed;

    public function files(string $key = null, mixed $default = null): mixed;

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed|UploadedFile
     */
    public function file(string $key, mixed $default = null): mixed;

    public function hasFile(string $key): bool;

    public function all(): array;

    public function input(string $key = null, mixed $default = null): mixed;

    public function only(string|array $keys): array;

    public function except(string|array $keys): array;

    public function has(string|array $keys): bool;

    public function hasAny(string|array $keys): bool;

    public function whenHas(string $key, callable $callback, callable $default = null): static;

    public function filled(string|array $keys): bool;

    public function notFilled(string|array $keys): bool;

    public function anyFilled(string|array $keys): bool;

    public function whenFilled(string $key, callable $callback, callable $default = null): static;

    public function missing(string|array $keys): bool;

    public function whenMissing(string $key, callable $callback, callable $default = null): static;

    public function string(string $key, ?string $default = ''): ?string;

    public function str(string $key, ?string $default = ''): ?string;

    public function integer(string $key, ?int $default = 0): ?int;

    public function int(string $key, ?int $default = 0): ?int;

    public function float(string $key, ?float $default = 0.0): ?float;

    public function boolean(string $key, ?bool $default = false): ?bool;

    public function bool(string $key, ?bool $default = false): ?bool;

    public function ip(): string;

    public function userAgent(): string;

    public function bearerToken(): ?string;

    public function getUser(): ?string;

    public function getPassword(): ?string;

    public function ajax(): bool;

    public function pajax(): bool;

    public function prefetch(): bool;

    public function getAcceptableTypes(): array;

    public function accepts(string|array $contentTypes): bool;

    public function acceptsAny(): bool;

    public function acceptsJson(): bool;

    public function acceptsHtml(): bool;

    public function format(string $default = 'html'): string;

    public function expectsJson(): bool;

    public function wantsJson(): bool;

    public function getAcceptedLanguages(string|array $languages = null): array;

    public function acceptLanguage(string $language): bool;

    public function getRoute(): ?Route;

    public function setRoute(Route $route): static;

    public function route(Route $route = null): static|Route|null;

    public function setSession(Session $session): void;

    public function hasSession(): bool;

    public function getSession(): Session;

    public function session(): Session;

    public function old(string $key, mixed $default = null): mixed;

    public function flash(): void;

    public function flashOnly(string|array $keys): void;

    public function flashExcept(string|array $keys): void;

    public function flush(): void;
}