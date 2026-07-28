<?php declare(strict_types=1);

namespace Imhotep\Support\Traits;

use Imhotep\Support\Str;

trait DeprecatedGetters
{
    protected function deprecatedGettersCall(string $method, array $parameters): mixed
    {
        if (str_starts_with($method, 'get')) {
            $newMethod = Str::lcfirst(substr($method, 3));

            if (method_exists($this, $newMethod)) {
                trigger_error(
                    sprintf('Method %s() is deprecated. Use %s() instead in %s class.', $method, $newMethod, get_class($this)),
                    E_USER_DEPRECATED
                );

                return $this->{$newMethod}(...$parameters);
            }
        }

        return null;
    }
}