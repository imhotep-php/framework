<?php declare(strict_types=1);

namespace Imhotep\Contracts\Http;

use ArrayAccess;
use Closure;
use Imhotep\Contracts\Routing\Route;
use Imhotep\Contracts\Session\ISession;

/**
 * @method array validate(array $rules, ...$params)
 */
interface Request extends ArrayAccess
{
    public static function createFromGlobals(): static;

    public static function create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], ?string $content = null): static;

    public function method(): string;

    public function isMethod(string|array $methods): bool;

    public function secure(): bool;

    public function scheme(): string;

    public function host(bool $withPort = false): string;

    public function port(): int;

    public function path(): string;

    public function queryString(): string;

    public function root(): string;

    public function url(array $query = []): string;

    public function fullUrl(array $query, array $without): string;

    public function uri(): string;

    public function server(?string $key = null, mixed $default = null): mixed;

    public function headers(string|array|null $keys = null): array;

    public function header(string $key, mixed $default = null): mixed;

    public function cookies(string|array|null $keys = null): mixed;

    public function cookie(string $key, mixed $default = null): mixed;

    public function query(?string $key = null, mixed $default = null): mixed;

    public function post(?string $key = null, mixed $default = null): mixed;

    public function json(?string $key = null, mixed $default = null): mixed;

    public function files(string|array|null $keys = null): array;

    public function file(string $key, mixed $default = null): mixed;

    public function hasFile(string $key): bool;

    public function all(): array;

    public function input(?string $key = null, mixed $default = null): mixed;

    public function only(string|array $keys): array;

    public function except(string|array $keys): array;

    public function has(string|array $keys): bool;

    public function hasAny(string|array $keys): bool;

    public function whenHas(string $key, callable $callback, ?callable $default = null): static;

    public function filled(string|array $keys): bool;

    public function notFilled(string|array $keys): bool;

    public function anyFilled(string|array $keys): bool;

    public function whenFilled(string $key, callable $callback, ?callable $default = null): static;

    public function missing(string|array $keys): bool;

    public function whenMissing(string $key, callable $callback, ?callable $default = null): static;

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

    public function getAcceptedLanguages(string|array|null $languages = null): array;

    public function acceptLanguage(string $language): bool;

    public function getRouteResolver(): Closure;

    public function setRouteResolver(Closure $resolver): static;

    public function route(): ?Route;

    public function setSession(ISession $session): void;

    public function hasSession(): bool;

    public function getSession(): ISession;

    public function session(): ISession;

    public function old(string $key, mixed $default = null): mixed;

    public function flash(): void;

    public function flashOnly(string|array $keys): void;

    public function flashExcept(string|array $keys): void;

    public function flush(): void;
}