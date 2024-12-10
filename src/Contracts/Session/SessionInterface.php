<?php declare(strict_types=1);

namespace Imhotep\Contracts\Session;

use Closure;
use Imhotep\Contracts\Http\Request;
use SessionHandlerInterface;

interface SessionInterface
{
    public function isStarted();

    public function getName(): string;

    public function setName(string $name): void;

    public function getId(): string;

    public function setId(?string $id): void;

    public function start();

    public function save();


    public function all();

    public function only(array|string $keys): array;

    public function missing(string $key): bool;

    public function has(string $key): bool;

    public function hasAny(array|string $keys): bool;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, string|int|float|bool|array $value): static;

    public function put(string $key, string|int|float|bool|array $value): static;

    public function push(string $key, string|int|float|bool|array $value): static;

    public function increment(string $key, int $amount = 1): int;

    public function decrement(string $key, int $amount = 1): int;

    public function delete(string $key): mixed;

    public function forget(string|array $keys): static;

    public function flush(): static;

    public function remember(string $key, Closure $callback): static;

    public function now(string $key, string|int|float|bool|array $value): static;

    public function flash(string $key, string|int|float|bool|array $value): static;

    public function reflash(): static;

    public function keep(string|array $keys): static;

    public function getOldInput(string $key = null, mixed $default = null): mixed;

    public function hasOldInput(string $key = null): bool;

    public function flashInput(array $value): static;


    public function getPreviousUrl();

    public function setPreviousUrl(string $url): static;


    public function csrf(): string;

    public function regenerateCsrf(): static;

    public function invalidate();

    public function regenerate(bool $destroy = false);

    public function migrate(bool $destroy = false);

    public function getHandler(): SessionHandlerInterface;

    public function setHandler(SessionHandlerInterface $handler): static;

    public function setRequestOnHandler(Request $request): static;

    public function getConfig();

    public function garbageCollect(): static;
}
