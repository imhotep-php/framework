<?php

declare(strict_types=1);

namespace Imhotep\Session;

use Imhotep\Contracts\Session\Session;
use Imhotep\Support\Arr;
use Imhotep\Support\Str;
use SessionHandlerInterface;

class Store implements Session
{
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

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $this->isValidId($id) ? $id : $this->generateSessionId();
    }

    public function isValidId(mixed $id): bool
    {
        return is_string($id) && ctype_alnum($id) && strlen($id) === 40;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function start(): bool
    {
        if ($data = $this->handler->read($this->id)){
            $data = json_decode($data, true);
            $this->attributes = array_merge($this->attributes, $data);
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
        $this->handler->write($this->id, $data);

        $this->attributes = [];

        $this->started = false;
    }

    public function all(): array
    {
        return $this->attributes;
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function missing(string $key): bool
    {
        return ! $this->exists($key);
    }

    public function has(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->attributes, $key, $default);
    }

    public function set(string $key, string|int|float|bool|array $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function put(string $key, string|int|float|bool|array $value): void
    {
        $this->set($key, $value);
    }

    public function push(string $key, string|int|float|bool|array $value): void
    {
        $array = $this->get($key, []);

        if (is_array($array)) {
            $array[] = $value;

            $this->put($key, $array);;
        }
    }

    public function delete(string $key): mixed
    {
        $value = null;

        if ($this->exists($key)) {
            $value = $this->attributes[$key];
            unset($this->attributes[$key]);
        }

        return $value;
    }

    public function forget(string|array $keys): void
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            unset($this->attributes[$key]);
        }
    }

    public function flush(): void
    {
        $this->attributes = [];
    }


    public function now(string $key, string|int|float|bool|array $value): void
    {
        $this->put($key, $value);

        $this->push('_flash_old', $key);
    }

    public function flash(string $key, string|int|float|bool|array $value): void
    {
        $this->put($key, $value);

        $this->push('_flash_new', $key);

        $this->removeKeysFromOldFlash([$key]);
    }

    public function reflash(): void
    {
        $oldKeys = $this->get('_flash_old', []);
        $newKeys = $this->get('_flash_new', []);
        $this->put('_flash_new', array_unique(array_merge($newKeys, $oldKeys)));
        $this->put('_flash_old', []);
    }

    public function keep(string|array $keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $this->put('_flash_new', array_unique(array_merge($this->get('_flash_new', []), $keys)));

        $this->removeKeysFromOldFlash($keys);
    }

    protected function removeKeysFromOldFlash(array $keys)
    {
        $this->put('_flash_old', array_diff($this->get('_flash_old', []), $keys));
    }

    public function getOldInput(string $key = null, mixed $default = null): mixed
    {
        return Arr::get($this->get('_input_old', []), $key, $default);
    }

    public function hasOldInput(string $key = null): bool
    {
        $old = $this->getOldInput($key);

        return is_null($key) ? count($old) > 0 : ! is_null($old);
    }

    public function flashInput(array $value): void
    {
        $this->flash('_input_old', $value);
    }


    public function csrf(): ?string
    {
        return $this->get('_csrf');
    }

    public function regenerateCsrf(): void
    {
        $this->set('_csrf', $this->generateSessionId());
    }

    public function invalidate(): bool
    {
        $this->flush();

        $this->regenerateCsrf();

        return $this->migrate(true);
    }

    public function regenerate($destroy = false): bool
    {
        $this->regenerateCsrf();

        return $this->migrate($destroy);
    }

    public function migrate($destroy = false): bool
    {
        if ($destroy) {
            $this->handler->destroy($this->id);
        }

        $this->setId($this->generateSessionId());

        return true;
    }

    protected function generateSessionId(): string
    {
        return Str::random(40);
    }

    public function isStarted(): bool
    {
        return $this->started;
    }

    public function previousUrl(): string
    {
        return $this->get('previous_url');
    }

    public function setPreviousUrl(string $url)
    {
        $this->set('previous_url', $url);
    }

    public function getHandler(): SessionHandlerInterface
    {
        return $this->handler;
    }

    public function handlerNeedsRequest()
    {
        // TODO: Implement handlerNeedsRequest() method.
    }

    public function setRequestOnHandler($request)
    {
        // TODO: Implement setRequestOnHandler() method.
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}