<?php declare(strict_types=1);

namespace Imhotep\Support;

use Closure;
use Imhotep\Container\BindingResolutionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

class Reflector
{
    public static function isCallable($callable, bool $syntaxOnly = false): bool
    {
        if (! is_array($callable)) {
            return is_callable($callable, $syntaxOnly);
        }

        if (! isset($callable[0], $callable[1]) || ! is_string($callable[1] ?? null)) {
            return false;
        }

        if ($syntaxOnly &&
            (is_string($callable[0]) || is_object($callable[0])) &&
            is_string($callable[1])) {
            return true;
        }

        $class = is_object($callable[0]) ? get_class($callable[0]) : $callable[0];

        $method = $callable[1];

        if (! class_exists($class)) {
            return false;
        }

        if (method_exists($class, $method)) {
            return (new \ReflectionMethod($class, $method))->isPublic();
        }

        if (is_object($callable[0]) && method_exists($class, '__call')) {
            return (new \ReflectionMethod($class, '__call'))->isPublic();
        }

        if (! is_object($callable[0]) && method_exists($class, '__callStatic')) {
            return (new \ReflectionMethod($class, '__callStatic'))->isPublic();
        }

        return false;
    }

    public static function resolveDependencies($container, $callable, array $parameters = []): array
    {
        if ( !($reflector = static::getMethodReflector($callable)) ) {
            return $parameters;
        }

        $dependencies = [];

        foreach ($reflector->getParameters() as $parameter) {
            $paramName = $parameter->getName();

            if ( array_key_exists($paramName, $parameters) ) {
                $dependencies[] = $parameters[$paramName];
                unset($parameters[$paramName]);
            }
            elseif ( ! is_null($className = static::getParameterClassName($parameter)) ) {
                if (array_key_exists($className, $parameters)) {
                    $dependencies[] = $parameters[$className];
                    unset($parameters[$className]);
                }

                $variadicDependencies = $container->make($className);

                if ($parameter->isVariadic()) {
                    $dependencies = array_merge($dependencies, is_array($variadicDependencies)
                        ? $variadicDependencies
                        : [$variadicDependencies]);
                }
                else {
                    $dependencies[] = $variadicDependencies;
                }


                /*
                elseif ($parameter->isVariadic()) {
                    $variadicDependencies = $container->make($className);

                    $dependencies = array_merge($dependencies, is_array($variadicDependencies)
                        ? $variadicDependencies
                        : [$variadicDependencies]);
                }
                else {
                    $dependencies[] = $container->make($className);
                }
                */
            }
            elseif ( $parameter->isDefaultValueAvailable() ) {
                $dependencies[] = $parameter->getDefaultValue();
            }
            elseif (! $parameter->isOptional() && ! array_key_exists($paramName, $parameters)) {
                $message = "Unable to resolve dependency [{$parameter}] in class {$parameter->getDeclaringClass()->getName()}";

                throw new BindingResolutionException($message);
            }
        }

        return array_merge($dependencies, $parameters);
    }

    public static function getMethodReflector($callable): ?ReflectionFunctionAbstract
    {
        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        if (is_string($callable)) {
            if (function_exists($callable)) {
                return new ReflectionFunction($callable);
            }
            elseif (str_contains($callable, '::')) {
                $callable = explode('::', $callable);
            }
            elseif (str_contains($callable, '@')) {
                $callable = explode('@', $callable);
            }
        }
        elseif (is_object($callable)) {
            $callable = [$callable, '__invoke'];
        }

        if (! method_exists($callable[0], $callable[1]) ) {
            return null;
        }

        return new ReflectionMethod($callable[0], $callable[1]);
    }

    public static function getParameterClassName($parameter)
    {
        $type = $parameter->getType();

        if (! $type instanceof \ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();

        if (! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }
}