<?php declare(strict_types=1);

namespace Imhotep\Session;

use Closure;
use Imhotep\Contracts\Http\Request;
use Imhotep\Contracts\Session\SessionInterface;
use Imhotep\Support\Arr;
use Imhotep\Support\Str;
use Imhotep\Support\Traits\Macroable;
use SessionHandlerInterface;

class Store implements SessionInterface
{
    use Macroable;

    protected string $id;

    protected string $name;

    protected array $attributes = [];

    protected SessionHandlerInterface $handler;

    protected bool $started = false;

    protected array $config = [];

    public function __construct(SessionHandlerInterface $handler, array $config = [])
    {
        $this->handler = $handler;
        $this->config = $config;

        $this->name = $config['name'] ?? '';

        $this->id = $this->generateSessionId();
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $this->isValidId($id) ? $id : $this->generateSessionId();
    }

    protected function isValidId(mixed $id): bool
    {
        return is_string($id) && ctype_alnum($id) && strlen($id) === 40;
    }

    protected function generateSessionId(): string
    {
        return Str::random(40);
    }


    public function start(): bool
    {
        if ($data = $this->handler->read($this->id)){
            $data = json_decode($this->prepareReadData($data), true);

            if (is_array($data)) {
                $this->attributes = array_merge($this->attributes, $data);
            }
        }

        if ($this->missing('_csrf')) {
            $this->regenerateCsrf();
        }

        return $this->started = true;
    }

    public function save(): void
    {
        // Age Flash data
        $this->forget($this->get('_flash_old', []));
        $this->put('_flash_old', $this->get('_flash_new', []));
        $this->put('_flash_new', []);

        // Save data
        $data = json_encode($this->attributes);
        $this->handler->write($this->id, $this->prepareWriteData($data));

        $this->attributes = [];

        $this->started = false;
    }

    protected function prepareWriteData(string $data): string
    {
        return $data;
    }

    protected function prepareReadData(string $data): string
    {
        return $data;
    }


    public function all(): array
    {
        return $this->attributes;
    }

    public function only(array|string $keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        return Arr::only($this->attributes, $keys);
    }

    public function missing(string $key): bool
    {
        return ! Arr::missing($this->attributes, $key);
    }

    public function has(string $key): bool
    {
        return Arr::has($this->attributes, $key);
    }

    public function hasAny(array|string $keys): bool
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        return Arr::hasAny($this->attributes, $keys);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->attributes, $key, $default);
    }

    public function set(string $key, string|int|float|bool|array $value): static
    {
        Arr::set($this->attributes, $key, $value);

        return $this;
    }

    public function put(string $key, string|int|float|bool|array $value): static
    {
        return $this->set($key, $value);
    }

    public function push(string $key, string|int|float|bool|array $value): static
    {
        $array = $this->get($key, []);

        if (is_array($array)) {
            $array[] = $value;

            $this->put($key, $array);
        }

        return $this;
    }

    public function increment(string $key, int $amount = 1): int
    {
        $this->put($key, $value = $this->get($key, 0) + $amount);

        return $value;
    }

    public function decrement(string $key, int $amount = 1): int
    {
        $this->put($key, $value = $this->get($key, 0) - $amount);

        return $value;
    }

    public function delete(string $key): mixed
    {
        $value = $this->get($key);

        Arr::forget($this->attributes, $key);

        return $value;
    }

    public function forget(string|array $keys): static
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        Arr::forget($this->attributes, $keys);

        return $this;
    }

    public function flush(): static
    {
        $this->attributes = [];

        return $this;
    }


    public function remember(string $key, Closure $callback): static
    {
        if (! is_null($value = $this->get($key))) {
            return $value;
        }

        $this->put($key, $value = $callback());

        return $value;
    }

    public function now(string $key, string|int|float|bool|array $value): static
    {
        return $this->put($key, $value)->push('_flash_old', $key);
    }

    public function flash(string $key, string|int|float|bool|array $value): static
    {
        $this->put($key, $value)->push('_flash_new', $key);

        $this->removeKeysFromOldFlash([$key]);

        return $this;
    }

    public function reflash(): static
    {
        return $this->put('_flash_new', array_unique(array_merge(
            $this->get('_flash_new', []), $this->get('_flash_old', [])
        )))->put('_flash_old', []);
    }

    public function keep(string|array $keys): static
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $this->put('_flash_new', array_unique(array_merge($this->get('_flash_new', []), $keys)));

        $this->removeKeysFromOldFlash($keys);

        return $this;
    }

    protected function removeKeysFromOldFlash(array $keys): void
    {
        $this->put('_flash_old', array_diff($this->get('_flash_old', []), $keys));
    }

    public function getOldInput(?string $key = null, mixed $default = null): mixed
    {
        return Arr::get($this->get('_input_old', []), $key, $default);
    }

    public function hasOldInput(?string $key = null): bool
    {
        $old = $this->getOldInput($key);

        return is_null($key) ? count($old) > 0 : ! is_null($old);
    }

    public function flashInput(array $value): static
    {
        $this->flash('_input_old', $value);

        return $this;
    }


    public function previousUrl(): string
    {
        return $this->getPreviousUrl();
    }

    public function getPreviousUrl(): string
    {
        return $this->get('previous_url');
    }

    public function setPreviousUrl(string $url): static
    {
        $this->set('previous_url', $url);

        return $this;
    }


    public function csrf(): string
    {
        if ($this->missing('csrf')) {
            $this->regenerateCsrf();
        }

        return $this->get('_csrf');
    }

    public function regenerateCsrf(): static
    {
        return $this->set('_csrf', $this->generateSessionId());
    }

    public function invalidate(): bool
    {
        return $this->flush()->regenerate(true);
    }

    public function regenerate(bool $destroy = false): bool
    {
        return $this->regenerateCsrf()->migrate($destroy);
    }

    public function migrate(bool $destroy = false): bool
    {
        if ($destroy) {
            $this->handler->destroy($this->id);
        }

        $this->setId($this->generateSessionId());

        return true;
    }

    public function getHandler(): SessionHandlerInterface
    {
        return $this->handler;
    }

    public function setHandler(SessionHandlerInterface $handler): static
    {
        $this->handler = $handler;

        return $this;
    }

    public function setRequestOnHandler(Request $request): static
    {
        if (method_exists($this->handler, 'setRequest')) {
            $this->handler->setRequest($request);
        }

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function garbageCollect(): static
    {
        $lottery = $this->config['lottery'] ?? [2, 100];

        if (! is_array($lottery) || count($lottery) !== 2) {
            $lottery = [2, 100];
        }

        if (random_int(1, $lottery[1]) <= $lottery[0]) {
            $this->handler->gc(0);
        }

        return $this;
    }
}