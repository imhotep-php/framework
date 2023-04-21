<?php

declare(strict_types=1);

namespace Imhotep\Container;

use ArrayAccess;
use Closure;
use Exception;
use Imhotep\Support\Reflector;
use Imhotep\Support\RewindableGenerator;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

class Container implements ArrayAccess, ContainerInterface
{
    protected static ?object $instance = null;

    protected array $bindings = [];

    protected array $instances = [];

    protected array $scopedInstances = [];

    protected array $aliases = [];

    protected array $abstractAliases = [];

    protected array $buildStack = [];

    protected array $resolved = [];

    public static function getInstance(): object
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    public static function setInstance(?Container $instance): ?object
    {
        return static::$instance = $instance;
    }

    public function bound($abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            $this->isAlias($abstract);
    }

    public function resolved($abstract): bool
    {
        $abstract = $this->getAlias($abstract);

        return isset($this->resolved[$abstract]) || isset($this->instances[$abstract]);
    }


    // Work with singleton
    public function instance(string $abstract, mixed $instance): mixed
    {
        $abstract = $this->getAlias($abstract);
        //$concrete = $this->getConcrete($abstract);

        //$this->removeAbstractAlias($abstract);

        //$isBound = $this->bound($abstract);

        //unset($this->aliases[$abstract]);

        // We'll check to determine if this type has been bound before, and if it has
        // we will fire the rebound callbacks registered with the container and it
        // can be updated with consuming classes that have gotten resolved here.
        $this->instances[$abstract] = $instance;

        //if ($isBound) {
        //$this->rebound($abstract);
        //}

        return $instance;
    }

    public function singleton(string $abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    public function singletonIf(string $abstract, $concrete = null)
    {
        if (! $this->bound($abstract)) {
            $this->singleton($abstract, $concrete);
        }
    }

    public function scoped(string $abstract, $concrete = null)
    {
        $this->scopedInstances[] = $abstract;

        $this->singleton($abstract, $concrete);
    }

    public function scopedIf(string $abstract, $concrete = null)
    {
        if (! $this->bound($abstract)) {
            $this->scoped($abstract, $concrete);
        }
    }

    public function forgetInstance($abstract)
    {
        unset($this->instances[$abstract]);
    }

    public function forgetInstances()
    {
        $this->instances = [];
    }

    public function forgetScopedInstances()
    {
        foreach ($this->scopedInstances as $scoped) {
            unset($this->instances[$scoped]);
        }
    }

    protected function dropStaleInstances($abstract)
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    // Bindings
    public function bind(string $abstract, Closure|string $concrete = null, bool $shared = false): void
    {
        unset($this->instances[$abstract]);

        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = compact('concrete', 'shared');

        if($this->resolved($abstract)){
          $this->rebound($abstract);
        }
    }

    public function bindIf(string $abstract, Closure|string $concrete = null, bool $shared = false): void
    {
        if (! $this->bound($abstract)) {
            $this->bind($abstract, $concrete, $shared);
        }
    }

    public function rebinding(string $abstract, Closure $callback): mixed
    {
        $abstract = $this->getAlias($abstract);

        $this->addContainerCallback('rebound', $abstract, $callback);

        if ($this->bound($abstract)) {
            return $this->make($abstract);
        }

        return null;
    }

    protected function rebound(string $abstract): void
    {
        $instance = $this->make($abstract);

        $this->callContainerCallbacks('rebound', $abstract, [$instance, $this]);
    }

    // Aliases
    public function alias(string $abstract, string|array $aliases): void
    {
        foreach ((array)$aliases as $alias) {
            if ($abstract === $alias) {
                throw new ContainerException("[$alias] is aliased to itself.");
            }

            $this->aliases[$alias] = $abstract;

            $this->abstractAliases[$abstract][] = $alias;
        }
    }

    public function getAlias($abstract): mixed
    {
        return (isset($this->aliases[$abstract]))
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    public function isAlias($abstract): bool
    {
        return isset($this->aliases[$abstract]);
    }

    protected function isShared($abstract): bool
    {
        return isset($this->instances[$abstract]) ||
            (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }

    protected function getConcrete($abstract): mixed
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    //public $make = 0;

    public function make(string $abstract, array $parameters = []): mixed
    {
        //$this->make++;
        //if ($this->make > 10) die();

        return $this->resolve2($abstract, $parameters);
    }

    protected array $resolveStack = [];

    protected function resolve2(string $abstract, array $parameters = [], bool $raiseEvents = true)
    {
        $this->resolveStack[] = $abstract;

        if ($raiseEvents) {
            $this->callContainerCallbacks('resolving_before', $abstract, [$abstract, $parameters, $this]);
        }

        $concrete = $this->getContextualConcrete($abstract);

        $needsContextualBuild = ! empty($parameters) || ! is_null($concrete);

        if (isset($this->instances[$abstract]) && ! $needsContextualBuild) {
            return $this->instances[$abstract];
        }

        if (isset($this->aliases[$abstract])) {
            $alias = $this->aliases[$abstract];
            if (! in_array($alias, $this->resolveStack)) {
                $concrete = $alias;
            }
        }

        if (is_null($concrete)) {
            $concrete = $this->getConcrete($abstract);
        }

        if ($abstract === $concrete || $concrete instanceof Closure) {
            $this->resolveStack = [];

            $object = $this->build($concrete, $parameters);
        }
        else {
            $object = $this->make($concrete, $parameters);
        }

        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }

        if ($this->isShared($abstract) && ! $needsContextualBuild) {
            $this->instances[$abstract] = $object;
        }

        $this->resolved[$abstract] = true;

        if ($raiseEvents) {
            $this->callContainerCallbacks('resolving_after', $abstract, [$object, $this]);
        }

        return $object;


        // MyRouteClass > router > instance | build
        // router > instance | build
        // RouterInterface > RouterClass > MyRouterClass > instance | build
        // RouterInterface > router > instance | build
    }

    protected function resolve(string $abstract, array $parameters = [], bool $raiseEvents = true): mixed
    {
        $abstract = $this->getAlias($abstract);

        if ($raiseEvents) {
            $this->callContainerCallbacks('resolving_before', $abstract, [$abstract, $parameters, $this]);
        }

        $concrete = $this->getContextualConcrete($abstract);

        $needsContextualBuild = ! empty($parameters) || ! is_null($concrete);

        if (isset($this->instances[$abstract]) && ! $needsContextualBuild) {
            return $this->instances[$abstract];
        }

        if (is_null($concrete)) {
            $concrete = $this->getConcrete($abstract);
        }

        if ($concrete === $abstract || $concrete instanceof Closure) {
            $object = $this->build($concrete, $parameters);
        } else {
            $object = $this->make($concrete, $parameters);
        }

        foreach ($this->getExtenders($abstract) as $extender) {
            $object = $extender($object, $this);
        }

        if ($this->isShared($abstract) && ! $needsContextualBuild) {
            $this->instances[$abstract] = $object;
        }

        $this->resolved[$abstract] = true;

        if ($raiseEvents) {
            $this->callContainerCallbacks('resolving_after', $abstract, [$object, $this]);
        }

        return $object;
    }

    /**
     * @throws Exception
     */
    public function build($concrete, array $parameters = []): mixed
    {
        // If the concrete type is actually a Closure, we will just execute it and
        // hand back the results of the functions, which allows functions to be
        // used as resolvers for more fine-tuned resolution of these objects.
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (Throwable $e) {
            throw new ContainerException("Target class [$concrete] does not exist.", $e->getCode(), $e);
        }

        // If the type is not instantiable, the developer is attempting to resolve
        // an abstract type such as an Interface or Abstract Class and there is
        // no binding registered for the abstractions so we need to bail out.
        if (! $reflector->isInstantiable()) {
            $this->notInstantiable($concrete);
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        // If there are no constructors, that means there are no dependencies then
        // we can just resolve the instances of the objects right away, without
        // resolving any other types or dependencies out of these containers.
        if (is_null($constructor)) {
            array_pop($this->buildStack);

            return new $concrete;
        }

        $dependencies = $constructor->getParameters();

        // Once we have all the constructor's parameters we can create each of the
        // dependency instances and then use the reflection instances to make a
        // new instance of this class, injecting the created dependencies in.
        try {
            $instances = $this->resolveDependencies($dependencies, $parameters);
        } catch (Exception $e) {
            array_pop($this->buildStack);

            throw $e;
        }

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Throw an exception that the concrete is not instantiable.
     *
     * @param string $concrete
     * @return void
     *
     * @throws ContainerException
     */
    protected function notInstantiable(string $concrete): void
    {
        if (! empty($this->buildStack)) {
            $previous = implode(', ', $this->buildStack);

            $message = "Target [$concrete] is not instantiable while building [$previous].";
        } else {
            $message = "Target [$concrete] is not instantiable.";
        }

        throw new ContainerException($message);
    }

    /*
    |--------------------------------------------------------------------------
    | Bound Method
    |--------------------------------------------------------------------------
    */
    public function call($callable, array $parameters = [], string $defaultMethod = null)
    {
        if (is_string($callable) && empty($defaultMethod) && method_exists($callable, '__invoke')) {
            $defaultMethod = '__invoke';
        }

        if ((is_string($callable) && str_contains($callable, '@')) || $defaultMethod) {
            $segments = explode('@', $callable);
            $method = (isset($segments[1])) ? $segments[1] : $defaultMethod;

            if (is_null($method)) {
                throw new ContainerException('Method not provided.');
            }

            $callable = [$this->make($segments[0]), $method];
        }

        if (is_array($callable)) {
            $method = (is_object($callable[0]) ? get_class($callable[0]) : $callable[0]).'@'.$callable[1];

            if ($this->hasMethodBinding($method)) {
                return $this->callMethodBinding($method, $callable[0]);
            }
        }

        return $callable(...array_values(
            Reflector::resolveDependencies($this, $callable, $parameters)
        ));
    }

    public function wrap(Closure $callback, array $parameters = []): Closure
    {
        return function () use ($callback, $parameters) {
            return $this->call($callback, $parameters);
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Method Binding
    |--------------------------------------------------------------------------
    */
    protected array $methods = [];

    public function bindMethod(string|array $method, Closure $callback): void
    {
        if (is_array($method)) {
            if (count($method) != 2) {
                return;
            }

            $method = $method[0].'@'.$method[1];
        }

        $this->methods[$method] = $callback;
    }

    public function hasMethodBinding(string $method): bool
    {
        return isset($this->methods[$method]);
    }

    public function callMethodBinding(string $method, object $instance): mixed
    {
        return call_user_func($this->methods[$method], $instance, $this);
    }

    public function forgetMethodBinding(string $method = null): void
    {
        if (is_null($method)) {
            $this->methods = [];
            return;
        }

        unset($this->methods[$method]);
    }

    // Extenders
    protected array $extenders = [];

    public function extend(string $abstract, Closure $callback)
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            $this->instances[$abstract] = $callback($this->instances[$abstract], $this);

            //$this->rebound($abstract);
        }
        else {
            $this->extenders[$abstract][] = $callback;

            //if ($this->resolved($abstract)) {
                //$this->rebound($abstract);
            //}
        }
    }

    protected function getExtenders(string $abstract): array
    {
        return $this->extenders[$this->getAlias($abstract)] ?? [];
    }

    /**
     * Remove all of the extender callbacks for a given type.
     *
     * @param  string  $abstract
     * @return void
     */
    public function forgetExtenders(string $abstract): void
    {
        unset($this->extenders[$this->getAlias($abstract)]);
    }


    // Tags
    protected array $tags = [];

    public function tag(string|array $abstracts, string|array $tags): void
    {
        $tags = is_array($tags) ? $tags : array_slice(func_get_args(), 1);

        foreach ($tags as $tag) {
            foreach ((array) $abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }

    public function tagged(string $tag): array|RewindableGenerator
    {
        if (! isset($this->tags[$tag])) {
            return [];
        }

        return new RewindableGenerator(function () use ($tag) {
            foreach ($this->tags[$tag] as $abstract) {
                yield $this->make($abstract);
            }
        }, count($this->tags[$tag]));
    }

    public function forgetTags(string|array $tags = null): void
    {
        if (is_null($tags)) {
            $this->tags = [];
            return;
        }

        $tags = is_array($tags) ? $tags : func_get_args();
        foreach ($tags as $tag) {
            if (! is_string($tag)) continue;

            unset($this->tags[$tag]);
        }
    }


    // Contextual bindings
    protected array $contextual = [];

    public function when(string|array $concrete): ContextualBindingBuilder
    {
        $aliases = [];

        foreach ((array)$concrete as $c) {
            $aliases[] = $this->getAlias($c);
        }

        return new ContextualBindingBuilder($this, $aliases);
    }

    public function addContextualBinding(string $concrete, string $abstract, Closure|string|array $implementation): void
    {
        $this->contextual[$concrete][$this->getAlias($abstract)] = $implementation;
    }

    protected function findInContextualBindings(string $abstract): mixed
    {
        return $this->contextual[end($this->buildStack)][$abstract] ?? null;
    }

    protected function getContextualConcrete(string $abstract): mixed
    {
        if (! is_null($binding = $this->findInContextualBindings($abstract))) {
            return $binding;
        }

        if (! empty($this->abstractAliases[$abstract])) {
            foreach ($this->abstractAliases[$abstract] as $alias) {
                if (! is_null($binding = $this->findInContextualBindings($alias))) {
                    return $binding;
                }
            }
        }

        return null;
    }


    public function flush()
    {
        $this->aliases = [];
        $this->abstractAliases = [];
        $this->instances = [];
        $this->scopedInstances = [];
        $this->bindings = [];
        $this->resolved = [];
        $this->methods = [];
        $this->contextual = [];
        $this->extenders = [];
        $this->tags = [];
    }

    /*
    |--------------------------------------------------------------------------
    | Array Access
    |--------------------------------------------------------------------------
    */

    public function get(string $id): mixed
    {
        return $this->make($id);
    }

    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->bound($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->make($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->bind($offset, $value instanceof Closure ? $value : function () use ($value) {
            return $value;
        });
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->bindings[$offset], $this->instances[$offset], $this->resolved[$offset]);
    }

    public function __get(string $key)
    {
        return $this[$key];
    }

    public function __set(string $key, mixed $value)
    {
        $this[$key] = $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Callbacks
    |--------------------------------------------------------------------------
    */

    protected array $reboundCallbacks = [];

    protected array $beforeResolvingCallbacks = [];

    protected array $afterResolvingCallbacks = [];

    public function resolving(string|Closure $abstract, Closure $callback = null): void
    {
        $this->addContainerCallback('resolving_after', $abstract, $callback);
    }

    public function beforeResolving(string|Closure $abstract, Closure $callback = null): void
    {
        $this->addContainerCallback('resolving_before', $abstract, $callback);
    }

    public function afterResolving(string|Closure $abstract, Closure $callback = null): void
    {
        $this->addContainerCallback('resolving_after', $abstract, $callback);
    }

    protected function addContainerCallback(string $event, string|Closure $abstract, Closure $callback = null): void
    {
        if (is_string($abstract)) {
            $abstract = $this->getAlias($abstract);
        }
        else {
            $callback = $abstract;
            $abstract = '*';
        }

        if ($event === 'rebound') {
            $this->reboundCallbacks[$abstract][] = $callback;
        }
        elseif ($event === 'resolving_before') {
            $this->beforeResolvingCallbacks[$abstract][] = $callback;
        }
        elseif ($event === 'resolving' || $event === 'resolving_after') {
            $this->afterResolvingCallbacks[$abstract][] = $callback;
        }
    }

    protected function callContainerCallbacks(string $event, string $abstract, array $parameters): void
    {
        $abstract = $this->getAlias($abstract);

        $callbacks = [];

        if ($event === 'rebound') {
            $callbacks = array_merge(
                $this->reboundCallbacks['*'] ?? [],
                $this->reboundCallbacks[$abstract] ?? []
            );
        }
        elseif ($event === 'resolving_before') {
            $callbacks = array_merge(
                $this->beforeResolvingCallbacks['*'] ?? [],
                $this->beforeResolvingCallbacks[$abstract] ?? []
            );
        }
        elseif ($event === 'resolving_after') {
            $callbacks = array_merge(
                $this->afterResolvingCallbacks['*'] ?? [],
                $this->afterResolvingCallbacks[$abstract] ?? []
            );
        }

        foreach ($callbacks as $callback) {
            $callback(...$parameters);
        }
    }



    /*
    |--------------------------------------------------------------------------
    | Reflection
    |--------------------------------------------------------------------------
    */

    protected function resolveDependencies(array $dependencies, array $parameters = []): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If the dependency has an override for this particular build we will use
            // that instead as the value. Otherwise, we will continue with this run
            // of resolutions and let reflection attempt to determine the result.

            if ( array_key_exists($name = $dependency->getName(), $parameters) ) {
                $results[] = $parameters[$name];
                //unset($parameters[$name]);
                continue;
            }

            /*if ($this->hasParameterOverride($dependency)) {
              $results[] = $this->getParameterOverride($dependency);

              continue;
            }*/

            // If the class is null, it means the dependency is a string or some other
            // primitive type which we can not resolve since it is not a class and
            // we will just bomb out with an error since we have no-where to go.
            $className = $this->getParameterClassName($dependency);

            $result = is_null($className)
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency, $className);

            if ($dependency->isVariadic()) {
                $results = array_merge($results, $result);
            } else {
                $results[] = $result;
            }
        }

        return $results;
    }

    protected function resolvePrimitive(ReflectionParameter $parameter): mixed
    {
        if (! is_null($concrete = $this->getContextualConcrete('$'.$parameter->getName()))) {
            return ($concrete instanceof Closure) ? $concrete($this) : $concrete;
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->isVariadic()) {
            return [];
        }

        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new ContainerException($message);
    }

    protected function resolveClass(ReflectionParameter $parameter, $className = null): mixed
    {
        try {
            return $parameter->isVariadic()
                ? $this->resolveVariadicClass($parameter, $className)
                : $this->make($className);
        }

            // If we can not resolve the class instance, we will check to see if the value
            // is optional, and if it is we will return the optional parameter value as
            // the value of the dependency, similarly to how we do this with scalars.
        catch (Exception $e) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            if ($parameter->isVariadic()) {
                return [];
            }

            throw new ContainerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Resolve a class based variadic dependency from the container.
     *
     * @param ReflectionParameter $parameter
     * @return mixed
     */
    protected function resolveVariadicClass(ReflectionParameter $parameter, $className): mixed
    {
        //$className = $this->getParameterClassName($parameter);

        $abstract = $this->getAlias($className);

        if (! is_array($concrete = $this->getContextualConcrete($abstract))) {
            return $this->make($className);
        }

        return array_map(function ($abstract) {
            return $this->resolve($abstract);
        }, $concrete);
    }

    /**
     * Get the class name of the given parameter's type, if possible.
     *
     * From Reflector::getParameterClassName() in Illuminate\Support.
     *
     * @param ReflectionParameter $parameter
     * @return string|null
     */
    public function getParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
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

    /*
    |--------------------------------------------------------------------------
    | Debug
    |--------------------------------------------------------------------------
    */

    public function getBindings(): array
    {
        $result = [];

        foreach ($this->bindings as $abstract => $data) {
            if ($abstract instanceof Closure) {
                $data['concrete'] = 'closure';
            }
            elseif (is_object($data['concrete'])) {
                $data['concrete'] = get_class($data['concrete']);
            }

            $result[$abstract] = [
                'concrete' => $data['concrete'],
                'shared' => $data['shared'],
            ];
        }

        return $result;
    }

    public function getAliases(): array
    {
        $result = [];

        foreach ($this->aliases as $alias => $abstract) {
            if ($abstract instanceof Closure) {
                $abstract = 'closure';
            }
            elseif (is_object($abstract)) {
                $abstract = get_class($abstract);
            }

            $result[$alias] = $abstract;
        }

        return $result;
    }

    public function getInstances(): array
    {
        $result = [];

        foreach ($this->instances as $concrete => $instance) {
            if ($instance instanceof Closure) {
                $instance = 'closure';
            }
            elseif (is_object($instance)) {
                $instance = get_class($instance);
            }

            $result[$concrete] = $instance;
        }

        return $result;
    }

    public function getContextual(): array
    {
        return $this->contextual;
    }

    public function getDebug(): array
    {
        return [
            'instances' => $this->getInstances(),
            'bindings' => $this->getBindings(),
            'aliases' => $this->getAliases(),
            'contextual' => $this->getContextual(),
        ];
    }
}