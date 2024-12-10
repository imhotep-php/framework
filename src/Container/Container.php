<?php declare(strict_types = 1);

namespace Imhotep\Container;

use Closure;
use Imhotep\Contracts\ContainerInterface;
use Imhotep\Support\Reflector;
use Imhotep\Support\RewindableGenerator;
use LogicException;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use Throwable;

class Container implements ContainerInterface
{
    use Traits\HasMethodBindings, Traits\HasCallbacks, Traits\HasContextual;

    /**
     * Экземпляр контейнера
     *
     * @var Container|null
     */
    protected static ?Container $instance = null;

    /**
     * Возвращает текущий экземпляр контейнера
     *
     * @return Container
     */
    public static function getInstance(): Container
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Устанавливает или очищает экземпляр контейнера
     *
     * @param Container|null $instance
     * @return Container|null
     */
    public static function setInstance(?Container $instance): ?Container
    {
        return static::$instance = $instance;
    }


    protected array $definitions = [];

    public function definition(string $abstract): ?Definition
    {
        $abstract = $this->getAlias($abstract);

        foreach ($this->definitions as $definition) {
            if ($definition->match($abstract)) {
                return $definition;
            }
        }

        return null;
    }

    protected function getDefinitionOrNew(string $abstract): Definition
    {
        if ($definition = $this->definition($abstract)) {
            return $definition;
        }

        return new Definition($this, $abstract);
    }

    protected function newDefinition(string $abstract): Definition
    {
        return $this->definitions[] = new Definition($this, $abstract);
    }

    protected function forgetDefinition(Definition $definition): void
    {
        $key = array_search($definition, $this->definitions);
        unset($this->definitions[$key]);
    }


    protected array $aliases = [];

    protected array $abstractAliases = [];

    public function alias(string $abstract, string|array $aliases): static
    {
        foreach ((array)$aliases as $alias) {
            if ($abstract === $alias) {
                throw new LogicException(sprintf('Alias [%s] is aliased to itself.', $alias));
            }

            $this->aliases[$alias] = $abstract;

            $this->abstractAliases[$abstract][] = $alias;
        }

        return $this;
    }

    public function getAlias(string $alias): string
    {
        return isset($this->aliases[$alias]) ? $this->getAlias($this->aliases[$alias]) : $alias;
    }

    public function hasAlias(string $alias): bool
    {
        return isset($this->aliases[$alias]);
    }



    /**
     * Добавить сформированный экземпляр в контейнер
     *
     * @param string $abstract
     * @param mixed $instance
     * @return $this
     */
    public function instance(string $abstract, mixed $instance): mixed
    {
        if (! ($definition = $this->definition($abstract))) {
            $definition = $this->newDefinition($abstract);
        }

        $definition->instance($instance);

        if ($definition->binded) {
            $definition->callRebound($instance);
        }

        $definition->binded();

        return $instance;
    }

    /**
     * Удалить сформированный экземпляр из контейнера
     *
     * @param string $abstract
     * @return $this
     */
    public function forgetInstance(string $abstract): static
    {
        $this->definition($abstract)?->instance(null);

        return $this;
    }

    /**
     * Удалить все сформированные экземпляры контейнера
     *
     * @return $this
     */
    public function forgetInstances(): static
    {
        foreach ($this->definitions as $definition) {
            $definition->instance(null)->shared(false);
        }

        return $this;
    }



    /**
     * Добавляет привязку к сущности в контейнер
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @param bool $singleton
     * @return $this
     * @throws ContainerException
     */
    public function bind(string $abstract, string|Closure $concrete = null, bool $singleton = false, bool $scoped = false): static
    {
        if (! ($definition = $this->definition($abstract))) {
            $definition = $this->newDefinition($abstract);
        }

        $definition->concrete($concrete ?: $abstract);
        $definition->shared($singleton);
        $definition->scoped($scoped)->binded();

        if ($definition->resolved) {
            $definition->callRebound($this->get($abstract));
        }

        return $this;
    }

    /**
     * Добавляет привязку сущности в контейнер, если ранее не существовала
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @param bool $singleton
     * @return $this
     * @throws ContainerException
     */
    public function bindIf(string $abstract, string|Closure $concrete = null, bool $singleton = false): static
    {
        if ($this->has($abstract)) {
            return $this;
        }

        return $this->bind($abstract, $concrete, $singleton);
    }

    /**
     * Добавляет привязку сущности в контейнер как синглтон
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @return $this
     * @throws ContainerException
     */
    public function singleton(string $abstract, string|Closure $concrete = null): static
    {
        return $this->bind($abstract, $concrete, true);
    }

    /**
     * Добавляет привязку сущности в контейнер как синглтон, если ранее не существовала
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @return $this
     * @throws ContainerException
     */
    public function singletonIf(string $abstract, string|Closure $concrete = null): static
    {
        if (! $this->has($abstract)) {
            return $this->bind($abstract, $concrete, true);
        }

        return $this;
    }

    /**
     * Определяет, является ли сущность синглтоном
     *
     * @param string $abstract
     * @return bool
     */
    public function isSingleton(string $abstract): bool
    {
        if ($definition = $this->definition($abstract)) {
            return $definition->shared || ! is_null($definition->instance);
        }

        return false;
    }

    /**
     * Добавляет привязку ограниченной сущности в контейнер
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @param bool $singleton
     * @return $this
     * @throws ContainerException
     */
    public function scoped(string $abstract, string|Closure $concrete = null): static
    {
        return $this->bind($abstract, $concrete, true, true);
    }

    /**
     * Добавляет привязку ограниченной сущности в контейнер, если ранее не существовала
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @param bool $singleton
     * @return $this
     */
    public function scopedIf(string $abstract, string|Closure $concrete = null): static
    {
        if (! $this->has($abstract)) {
            $this->scoped($abstract, $concrete);
        }

        return $this;
    }

    /**
     * Определяет, является ли сущность ограниченной
     *
     * @param string $abstract
     * @return bool
     */
    public function isScoped(string $abstract): bool
    {
        return $this->definition($abstract)?->scoped ?? false;
    }

    /**
     * Удаляет из контейнера привязку ограниченной сущности
     *
     * @param string $abstract
     * @return $this
     */
    public function forgetScoped(string $abstract): static
    {
        if ($definition = $this->definition($abstract)) {
            $definition->forgetScoped();

            if($definition->isEmpty()) {
                $this->forgetDefinition($definition);
            }
        }

        return $this;
    }

    /**
     * Удаляет из контейнера сформированные экземпляры ограниченных сущностей
     *
     * @return $this
     */
    public function forgetScopedInstances(): static
    {
        foreach ($this->definitions as $definition) {
            if ($definition->scoped) {
                $definition->instance(null);
            }
        }

        return $this;
    }


    /**
     * Расширяет сущность через callback
     *
     * @param string $abstract
     * @param Closure $callback
     * @return $this
     */
    public function extend(string $abstract, Closure $callback): static
    {
        if (! ($definition = $this->definition($abstract))) {
            $definition = $this->newDefinition($abstract);
        }

        $definition->extend($callback);

        return $this;
    }

    /**
     * Удаление всех расширений сущности
     *
     * @param string $abstract
     * @return $this
     */
    public function forgetExtends(string $abstract): static
    {
        $this->definition($abstract)?->forgetExtends();

        return $this;
    }

    /**
     * Удаление всех расширений сущности
     *
     * @param string $abstract
     * @return $this
     */
    public function forgetExtenders(string $abstract): static
    {
        return $this->forgetExtends($abstract);
    }


    /**
     * Список зарегистрированных тегов
     *
     * @var array
     */
    protected array $tags = [];

    /**
     * Привязка тегов к сущностям
     *
     * @param string|array $abstracts
     * @param string|array $tags
     * @return void
     */
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
                yield $this->get($abstract);
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



    public function make(string $abstract, array $parameters = []): mixed
    {
        return $this->get($abstract, $parameters);
    }

    public function get(string $id, array $parameters = [], bool $raiseEvents = true): mixed
    {
        $abstract = $this->getAlias($id);

        $definition = $this->getDefinitionOrNew($abstract);

        if ($raiseEvents) {
            $this->callBeforeResolvingCallbacks($definition, $abstract, $parameters);
        }

        $concrete = $this->getContextualConcrete($abstract);

        $hasContextual = ! empty($parameters) || ! is_null($concrete);

        if ($hasContextual) {
            if ($concrete instanceof Closure) {
                return call_user_func($concrete, $this, $parameters);
            }

            if (is_array($concrete)) {
                return array_map(function ($abstract) {
                    return $this->get($abstract);
                }, $concrete);
            }

            if (is_string($concrete)) {
                return $this->get($concrete);
            }
        }

        if (! is_null($definition->instance) && ! $hasContextual) {
            return $definition->instance;
        }

        $object = $this->build($definition->concrete, $parameters);

        foreach ($definition->extends as $extender) {
            $object = $extender($object, $this);
        }

        if ($definition->shared && ! $hasContextual) {
            $definition->instance($object);
        }

        $definition->resolved = true;

        if ($raiseEvents) {
            $this->callAfterResolvingCallbacks($definition, $abstract, $object);
        }

        return $object;
    }



    public array $buildStack = [];

    public function build(string|Closure $concrete, array $parameters = []): mixed
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (Throwable $e) {
            throw new NotFoundException("Target class [$concrete] does not exist.", $e->getCode(), $e);
        }

        if (! $reflector->isInstantiable()) {
            if (! empty($this->buildStack)) {
                throw new ContainerException(sprintf(
                    'Target class [%s] is not instantiable while building [%s].',
                    $concrete, implode(' => ', $this->buildStack)
                ));
            }

            throw new ContainerException("Target class [$concrete] is not instantiable.");
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            array_pop($this->buildStack);

            return new $concrete;
        }

        try {
            $dependencies = $this->buildDependencies($constructor->getParameters(), $parameters);
        }
        catch (Throwable $e) {
            array_pop($this->buildStack);

            throw $e;
        }

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Подготовка зависимостей класса для их внедрения
     *
     * @param array $dependencies
     * @param array $parameters
     * @return array
     * @throws ContainerException
     */
    protected function buildDependencies(array $dependencies, array $parameters): array
    {
        $results = [];

        /** @var ReflectionParameter $dependency */
        foreach ($dependencies as $dependency) {

            // Override parameter
            if (isset($parameters[$name = $dependency->getName()])) {
                $results[] = $parameters[$name];
                unset($parameters[$name]);

                continue;
            }

            $result = null;
            $type = $dependency->getType();

            // Simple primitive (int, string, bool, array...)
            if ($type instanceof ReflectionNamedType && $type->isBuiltin() || is_null($type)) {
                $result = $this->buildDependencyPrimitive($dependency);
            }
            else {
                $result = $this->buildDependencyClass($dependency);
            }

            // Variadic is: fn(...$args)
            if ($dependency->isVariadic()) {
                $results = array_merge($results, $result, $parameters);
            }
            else {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Подготовка примитивной зависимости для последующего внедрения
     *
     * @param ReflectionParameter $dependency
     * @param array $parameters
     * @param Throwable|null $e
     * @return mixed
     * @throws ContainerException
     * @throws Throwable
     */
    protected function buildDependencyPrimitive(ReflectionParameter $dependency, Throwable $e = null): mixed
    {
        if (! is_null($concrete = $this->getContextualConcrete('$'.$dependency->getName()))) {
            return ($concrete instanceof Closure) ? $concrete($this) : $concrete;
        }

        if ($dependency->isDefaultValueAvailable()) {
            return $dependency->getDefaultValue();
        }

        if ($dependency->isVariadic()) {
            return [];
        }

        if (! is_null($e)) {
            throw $e;
        }

        throw new ContainerException(sprintf(
            'Unresolvable dependency resolving [$%s] in class {%s}.',
            $dependency->getName(), $dependency->getDeclaringClass()->getName()
        ));
    }

    /**
     * Подготовка зависимого класса для последующего внедрения
     *
     * @param ReflectionParameter $dependency
     * @param array $parameters
     * @return mixed
     * @throws ContainerException
     * @throws Throwable
     */
    protected function buildDependencyClass(ReflectionParameter $dependency): mixed
    {
        $className = $dependency->getType()->getName();

        try {
            return $this->get($className);
        }
        catch (Throwable $e) {
            return $this->buildDependencyPrimitive($dependency, $e);
        }
    }



    /**
     * Добавляем новую сущность в контейнер
     *
     * @param string $abstract
     * @param string|Closure|null $concrete
     * @param bool $singleton
     * @return $this
     * @throws ContainerException
     */
    public function set(string $abstract, string|Closure $concrete = null, bool $singleton = false): static
    {
        return $this->bind($abstract, $concrete, $singleton);
    }

    public function has(string $id): bool
    {
        return (bool)$this->definition($id);
    }

    public function bound(string $abstract): bool
    {
        return $this->has($abstract);
    }

    public function resolved(string $abstract): bool
    {
        if ($definition = $this->definition($abstract) ) {
            return $definition->resolved;
        }

        return false;
    }

    /**
     * Вызывает callback, если привязка была обновлена
     *
     * @param string|Closure $abstract
     * @param Closure|null $callback
     * @return mixed
     * @throws ContainerException
     */
    public function rebinding(string|Closure $abstract, Closure $callback = null): mixed
    {
        $hasAbstract = $this->has($abstract);

        if (is_string($abstract)) {
            if (! ($definition = $this->definition($abstract)) ) {
                $definition = $this->newDefinition($abstract)->concrete(null);
            }

            $definition->addRebound($callback);
        }else {
            $this->reboundCallbacks[] = $callback;
        }

        if ($hasAbstract) {
            return $this->get($abstract);
        }

        return null;
    }

    /**
     * Передает новый экземпляр в указанный метод объекта получателя
     *
     * @param string $abstract
     * @param object $target
     * @param string $method
     * @return mixed
     * @throws ContainerException
     */
    public function refresh(string $abstract, object $target, string $method): mixed
    {
        return $this->rebinding($abstract, function ($container, $instance) use ($target, $method) {
            return $target->{$method}($instance);
        });
    }

    public function call($callable, array $parameters = [], string $defaultMethod = null): mixed
    {
        $pushedToBuildStack = false;

        if (($className = $this->getClassForCallable($callable)) && ! in_array($className, $this->buildStack, true)) {
            $this->buildStack[] = $className;

            $pushedToBuildStack = true;
        }

        $result = $this->toCall($callable, $parameters, $defaultMethod);

        if ($pushedToBuildStack) {
            array_pop($this->buildStack);
        }


        return $result;
    }

    protected function toCall($callable, array $parameters = [], string $defaultMethod = null): mixed
    {
        if (is_string($callable) && empty($defaultMethod) && method_exists($callable, '__invoke')) {
            $defaultMethod = '__invoke';
        }

        if ((is_string($callable) && str_contains($callable, '@')) || $defaultMethod) {
            $segments = explode('@', $callable);
            $method = (isset($segments[1])) ? $segments[1] : $defaultMethod;

            if (is_null($method)) {
                throw new BindingResolutionException('Method not provided.');
            }

            $callable = [$this->get($segments[0]), $method];
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

    protected function getClassForCallable($callback): string|false
    {
        if (is_callable($callback) &&
            ! ($reflector = new ReflectionFunction($callback(...)))->isAnonymous()) {
            return $reflector->getClosureScopeClass()->name ?? false;
        }

        return false;
    }


    public function __get(string $key): mixed
    {
        return $this[$key];
    }

    public function __set(string $key, mixed $value): void
    {
        $this[$key] = $value;
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value instanceof Closure ? $value : function () use ($value) {
            return $value;
        });
    }

    public function offsetUnset(mixed $offset): void
    {
        if ($definition = $this->definition($offset)) {
            $definition->forgetAbstract($offset);

            if ($definition->isEmpty()) {
                $this->forgetDefinition($definition);
            }
        }
    }
}