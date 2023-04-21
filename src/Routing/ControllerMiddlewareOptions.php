<?php

declare(strict_types=1);

namespace Imhotep\Routing;

class ControllerMiddlewareOptions
{
    public function __construct (protected array &$options)
    {
    }

    /**
     * Set the controller methods the middleware should apply to.
     *
     * @param  array|string|dynamic  $methods
     * @return $this
     */
    public function only(array|string $methods)
    {
        $this->options['only'] = is_array($methods) ? $methods : func_get_args();

        return $this;
    }

    /**
     * Set the controller methods the middleware should exclude.
     *
     * @param  array|string|dynamic  $methods
     * @return $this
     */
    public function except(array|string $methods)
    {
        $this->options['except'] = is_array($methods) ? $methods : func_get_args();

        return $this;
    }
}