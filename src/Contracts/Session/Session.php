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

    /**
     * Get the current session ID.
     *
     * @return string
     */
    public function getId(): ?string;

    /**
     * Set the session ID.
     *
     * @param  string  $id
     * @return void
     */
    public function setId(?string $id): void;

    /**
     * Start the session, reading the data from a handler.
     *
     * @return bool
     */
    public function start();

    /**
     * Save the session data to storage.
     *
     * @return void
     */
    public function save();

    /**
     * Get all of the session data.
     *
     * @return array
     */
    public function all();

    /**
     * Checks if a key exists.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function exists($key);

    /**
     * Checks if a key is present and not null.
     *
     * @param  string|array  $key
     * @return bool
     */
    public function has($key);

    /**
     * Get an item from the session.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null): mixed;

    public function set(string $key, string|int|float|bool $value);

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
     * Remove all of the items from the session.
     *
     * @return void
     */
    public function flush();

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
