<?php

declare(strict_types=1);

namespace Imhotep\Contracts\Session;

interface Session
{
    /**
     * Get the name of the session.
     *
     * @return string
     */
    public function getName(): ?string;

    /**
     * Set the name of the session.
     *
     * @param  string  $name
     * @return void
     */
    public function setName(string $name): void;

    public function getId(): ?string;

    public function setId(?string $id): void;

    public function start();

    public function save();

    public function all();

    public function exists(string $key);

    public function missing(string $key): bool;

    public function has(string $key): bool;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, string|int|float|bool|array $value): void;

    public function put(string $key, string|int|float|bool|array $value): void;

    public function push(string $key, string|int|float|bool|array $value): void;

    public function delete(string $key): mixed;

    public function forget(string|array $keys): void;

    public function flush(): void;

    /**
     * Get the CSRF token value.
     *
     * @return string
     */
    public function csrf(): ?string;

    /**
     * Regenerate the CSRF token value.
     *
     * @return void
     */
    public function regenerateCsrf(): void;

    /**
     * Flush the session data and regenerate the ID.
     *
     * @return bool
     */
    public function invalidate();

    /**
     * Generate a new session identifier.
     *
     * @param  bool  $destroy
     * @return bool
     */
    public function regenerate($destroy = false);

    /**
     * Generate a new session ID for the session.
     *
     * @param  bool  $destroy
     * @return bool
     */
    public function migrate($destroy = false);

    /**
     * Determine if the session has been started.
     *
     * @return bool
     */
    public function isStarted();

    /**
     * Get the previous URL from the session.
     *
     * @return string|null
     */
    public function previousUrl();

    /**
     * Set the "previous" URL in the session.
     *
     * @param  string  $url
     * @return void
     */
    public function setPreviousUrl(string $url);

    /**
     * Get the session handler instance.
     *
     * @return \SessionHandlerInterface
     */
    public function getHandler(): \SessionHandlerInterface;

    /**
     * Determine if the session handler needs a request.
     *
     * @return bool
     */
    public function handlerNeedsRequest();

    /**
     * Set the request on the handler instance.
     *
     * @param  \Imhotep\Contracts\Http\Request  $request
     * @return void
     */
    public function setRequestOnHandler($request);
}
