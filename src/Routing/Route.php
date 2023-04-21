<?php

declare(strict_types=1);

namespace Imhotep\Routing;

use Closure;
use Imhotep\Closure\SerializableClosure;
use Imhotep\Container\Container;
use Imhotep\Contracts\Http\Request;
use Imhotep\Contracts\Routing\Route as RouteContract;
use Imhotep\Support\Reflector;
use Imhotep\Support\Traits\Macroable;

class Route implements RouteContract
{
    use Macroable;

    protected ?string $name = null;

    protected array $rules = [];

    protected ?string $regexDomain = null;

    protected ?string $regexUri = null;

    protected array $params = [];

    protected array $bindingFields = [];

    protected bool $actionParsed = false;

    protected array $groupAttributes = [];

    protected array $wheres = [];

    protected array $defaults = [];

    protected ?string $domain = null;

    public function __construct(
        protected string|array $methods,
        protected string $uri,
        protected string|array|Closure $action,
        protected ?Container $container = null
    )
    {
        if (! str_starts_with($this->uri, '/')) {
            $this->uri = "/" . $this->uri;
        }

        $this->methods = (array)$this->methods;

        $this->parseAction();
    }

    public function methods(): array
    {
        return $this->methods;
    }

    public function getMethods(): array
    {
        return $this->methods;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getAction(): string|array|Closure
    {
        return $this->action;
    }

    public function action(): string|array|Closure
    {
        return $this->action;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setAction(string|array|Closure $action): void
    {
        $this->action = $action;
    }

    public function setGroupAttributes(array $attributes): void
    {
        $this->groupAttributes = $attributes;
    }

    public function name(string $name = null): static|string|null
    {
        if (is_null($name)) {
            return $this->name;
        }

        $this->name = ($this->groupAttributes['name'] ?? '').$name;

        return $this;
    }

    public function named(string|array $patterns): bool
    {
        if (is_null($this->name)) {
            return false;
        }

        foreach ((array)$patterns as $pattern) {
            if (! is_string($pattern)) continue;

            if ($pattern === $this->name) {
                return true;
            }

            $pattern = preg_quote($pattern, '#');
            $pattern = str_replace('\*', '.*', $pattern);

            if (preg_match('#^'.$pattern.'\z#u', $this->name) === 1) {
                return true;
            }
        }

        return false;
    }

    public function domain(string $domain = null): static|string|null
    {
        if (is_null($domain)) {
            return $this->domain;
        }

        $this->domain = $domain;

        return $this;
    }

    public function where(string|array $name, string $regex = null): static
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->wheres[$key] = $val;
            }
        }
        elseif (! is_null($regex)){
            $this->wheres[$name] = $regex;
        }

        return $this;
    }

    public function whereNumber(string|array $name): static
    {
        return $this->whereMassAssign((array)$name, '[0-9]+');
    }

    public function whereAlpha(string|array $name): static
    {
        return $this->whereMassAssign((array)$name, '[A-z]+');
    }

    public function whereAlphaNumeric(string|array $name): static
    {
        return $this->whereMassAssign((array)$name, '[A-z0-9]+');
    }

    public function whereUuid(string|array $name): static
    {
        return $this->whereMassAssign((array)$name, '[\da-fA-F]{8}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{4}-[\da-fA-F]{12}');
    }

    public function whereIn(string|array $name, array $values): static
    {
        return $this->whereMassAssign((array)$name, implode('|', $values));
    }

    protected function whereMassAssign(array $names, string $regex): static
    {
        foreach ($names as $name) {
            $this->wheres[$name] = $regex;
        }

        return $this;
    }

    public function bindingFieldFor(string $parameter): mixed
    {
        $this->parseUri();

        return $this->bindingFields[$parameter] ?? null;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function setDefaults(array|string $name, mixed $value = null): static
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->defaults[$key] = $val;
            }
        }
        else {
            $this->defaults[$name] = $value;
        }

        return $this;
    }

    public function defaults(array|string $name = null, mixed $value = null): static|array
    {
        if (is_null($name)) {
            return $this->getDefaults();
        }

        return $this->setDefaults($name, $value);
    }

    public function parameters(): array
    {
        return $this->params;
    }

    protected function parse(): void
    {
        $this->parseDomain();
        $this->parseUri();
    }

    protected function parseDomain(): void
    {
        if (empty($this->domain)) return;

        if (! is_null($this->regexDomain)) return;

        $this->regexDomain = $this->getRegexFrom($this->domain, 'domain');
    }

    protected function parseUri(): void
    {
        if (! is_null($this->regexUri)) return;

        // Home route
        if ($this->uri === '/') {
            return;
        }

        //$this->rules = [];

        //$this->regexUri = $this->uri;

        $this->regexUri = $this->getRegexFrom($this->uri, 'uri');

        /*
        $hasOptional = null;
        if (preg_match_all("/{(.*?)}/", $this->uri, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            $matches = array_reverse($matches);

            foreach ($matches as $match) {
                $position = $match[0][1];
                $length = strlen($match[0][0]);

                if (preg_match("/^([A-z]+)(:(.*?))?(\?)?$/", $match[1][0], $match)) {
                    $name = $match[1];
                    $field = $match[3] ?? null;
                    $optional = isset($match[4]) ?? false;

                    if (is_null($hasOptional)){
                        $hasOptional = $optional;
                    }
                    elseif ($hasOptional) {
                        $optional = false;
                    }

                    $this->params[$name] = "";

                    if (! is_null($field)) {
                        $this->bindingFields[$name] = $field;
                    }

                    $regexBefore = substr($this->regexUri, 0, $position);
                    $regexAfter = substr($this->regexUri, $position + $length);
                    $regex = (isset($this->wheres[$name])) ? "(?P<{$name}>{$this->wheres[$name]})" : "(?P<{$name}>[^/]++)";

                    if ($optional) {
                        $separators = '/,;.:-_~+*=@|';
                        $symbol = substr($regexBefore, -1, 1);
                        if (str_contains($separators, $symbol)) {
                            $regexBefore = substr($regexBefore, 0, -1);
                            $regex = "(?:{$symbol}{$regex})?";
                        }
                    }

                    $this->regexUri = $regexBefore.$regex.$regexAfter;
                }
                else {
                    throw new \Exception("Parameter syntax invalid [".$match[0][0]."] in route [".$this->uri."]");
                }
            }
        }

        $this->regexUri = "{^{$this->regexUri}$}sDu";
        */
    }

    protected function getRegexFrom(string $string, string $type): string
    {
        $regex = $string;

        $separator = ($type === 'domain') ? '.' : '/';

        $hasOptional = null;

        if (preg_match_all("/{(.*?)}/", $string, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            $matches = array_reverse($matches);

            foreach ($matches as $match) {
                $position = $match[0][1];
                $length = strlen($match[0][0]);

                if (preg_match("/^([A-z]+)(:(.*?))?(\?)?$/", $match[1][0], $match)) {
                    $name = $match[1];
                    $field = $match[3] ?? null;
                    $optional = isset($match[4]) ?? false;

                    if (is_null($hasOptional)){
                        $hasOptional = $optional;
                    }
                    elseif ($hasOptional || $type === 'domain') {
                        $optional = false;
                    }

                    $this->params[$name] = null;

                    if (! is_null($field)) {
                        $this->bindingFields[$name] = $field;
                    }

                    $reBefore = substr($regex, 0, $position);
                    $reAfter = substr($regex, $position + $length);
                    $re = (isset($this->wheres[$name])) ? "(?P<{$name}>{$this->wheres[$name]})" : "(?P<{$name}>[^{$separator}]++)";

                    if ($optional) {
                        $symbol = substr($reBefore, -1, 1);
                        if (str_contains('/,;.:-_~+*=@|', $symbol)) {
                            $reBefore = substr($reBefore, 0, -1);
                            $re = "(?:{$symbol}{$re})?";
                        }
                    }

                    $regex = $reBefore.$re.$reAfter;
                }
                else {
                    throw new \Exception("Parameter syntax invalid [".$match[0][0]."] in route [".$this->uri."]");
                }
            }
        }

        return "{^{$regex}$}sDu";
    }

    public function matches(Request $request): bool
    {
        if (! $this->matchMethod($request)) {
            return false;
        }

        $this->parse();

        if (! $this->matchDomain($request)) {
            return false;
        }

        return $this->matchUri($request);
    }

    protected function matchMethod(Request $request): bool
    {
        return $request->isMethod($this->methods);
    }

    protected function matchDomain(Request $request): bool
    {
        if (empty($this->domain)) {
            return true;
        }

        if (preg_match($this->regexDomain, $request->host(), $match)) {
            foreach ($this->params as $key => $val) {
                if (isset($match[$key])) $this->params[$key] = $match[$key];
            }

            return true;
        }

        return false;
    }

    protected function matchUri(Request $request): bool
    {
        $uri = urldecode($request->uri());

        if (! str_starts_with($uri, '/')) {
            $uri = '/'.$uri;
        }

        foreach ($this->defaults as $key => $val) {
            $this->params[$key] = $val;
        }

        if ($uri === $this->uri) {
            return true;
        }

        if ($this->uri === '/') {
            return false;
        }

        if (preg_match($this->regexUri, $uri, $match)) {
            foreach ($this->params as $key => $val) {
                if (isset($match[$key])) {
                    $this->params[$key] = $match[$key];
                }
            }

            return true;
        }

        return false;
    }

    public function run()
    {
        $this->parseAction();

        if ($this->action['type'] === 'controller') {
            return $this->runController();
        }

        if ($this->action['type'] === 'serialize') {
            $this->action['type'] = 'closure';
            $this->action['uses'] = unserialize($this->action['uses'])->getClosure();
        }

        return $this->runCallable();
    }

    protected function parseAction(): void
    {
        if ($this->actionParsed) return;
        $this->actionParsed = true;

        if (is_array($this->action) && isset($this->action['type']) && isset($this->action['uses'])) {
            if (in_array($this->action['type'], ['closure','controller','serialize'])) {
                return;
            }
        }

        if(Reflector::isCallable($this->action, true)) {
            if ($this->action instanceof Closure) {
                $this->action = ['type' => 'closure', 'uses' => $this->action];
            }
            elseif (is_array($this->action)) {
                $this->action = ['type' => 'controller', 'uses' => $this->action[0].'@'.$this->action[1]];
            }
            else {
                $this->action = ['type' => 'controller', 'uses' => $this->action];
            }

            return;
        }

        $this->action = ['type' => 'closure', 'uses' => function () {
            throw new \LogicException(sprintf("Route for [%s] has no action.", $this->uri));
        }];
    }

    protected function runCallable(){
        $parameters = Reflector::resolveDependencies(
            $this->container, $this->action['uses'], $this->params
        );

        return $this->action['uses'](...$parameters);
    }

    protected function isControllerAction(): bool
    {
        return is_string($this->action['uses']);
    }

    protected function runController()
    {
        $controller = $this->getController();
        $method = $this->getControllerMethod();

        $parameters = Reflector::resolveDependencies(
            $this->container, [$controller, $method], $this->params
        );

        if (method_exists($controller, 'callAction')) {
            return $controller->callAction($method, $parameters);
        }

        return $controller->{$method}(...$parameters);
    }

    protected $controller;

    protected function getController(): object
    {
        if ( ! $this->controller) {
            $this->controller = $this->container->make($this->getControllerClass());
        }

        return $this->controller;
    }

    protected function getControllerClass(): string
    {
        list($class, $method) = explode("@", $this->action['uses']);
        return $class;
    }

    protected function getControllerMethod(): string
    {
        list($class, $method) = explode("@", $this->action['uses']);
        return $method;
    }

    protected function getControllerMiddleware(): array
    {
        $controller = $this->getController();
        $method = $this->getControllerMethod();

        $middlewares = [];

        if (method_exists($controller, 'getMiddlewares')) {
            $middlewares = $controller->getMiddlewares($method);
        }

        return $middlewares;
    }

    // Middleware
    protected array $middlewares = [];

    protected array $excludedMiddlewares = [];

    public function middleware(string|array|Closure $middleware = null): array|static
    {
        if (is_null($middleware)) {
            return $this->middlewares;
        }

        if (is_string($middleware) || $middleware instanceof Closure) {
            $this->middlewares[] = $middleware;
        }
        elseif (is_array($middleware)) {
            foreach ($middleware as $value) {
                if (!is_string($value)) continue;
                $this->middlewares[] = $value;
            }
        }

        return $this;
    }

    public function withoutMiddleware(string|array $middleware): static
    {
        foreach ((array)$middleware as $name) {
            if (is_string($name)) {
                $this->excludedMiddlewares[] = $name;
            }
        }

        return $this;
    }

    public function getMiddleware(): array
    {
        $this->parseAction();

        $middlewares = [];

        if (isset($this->groupAttributes['middleware'])) {
            $middlewares = $this->groupAttributes['middleware'];
        }

        $middlewares = array_merge($middlewares, $this->middlewares);

        if ($this->action['type'] === 'controller') {
            $middlewares = array_merge($middlewares, $this->getControllerMiddleware());
        }

        return $middlewares;
    }

    public function getExcludedMiddleware(): array
    {
        return $this->excludedMiddlewares;
    }

    public function cache(array $data = null)
    {
        if (is_null($data)) {
            $this->parse();
            $this->parseAction();

            return [
                'methods' => $this->methods,
                'domain' => $this->domain,
                'uri' => $this->uri,
                'action' => $this->cacheAction(),
                'regexUri' => $this->regexUri,
                'regexDomain' => $this->regexDomain,
                'params' => $this->params,
                'bindingFields' => $this->bindingFields,
                'wheres' => $this->wheres,
                'name' => $this->name,
            ];
        }

        if (isset($data['domain'])) {
            $this->domain = $data['domain'];
        }

        if (isset($data['regexUri'])) {
            $this->regexUri = $data['regexUri'];
        }

        if (isset($data['regexDomain'])) {
            $this->regexDomain = $data['regexDomain'];
        }

        if (isset($data['params'])) {
            $this->params = $data['params'];
        }

        if (isset($data['bindingFields'])) {
            $this->bindingFields = $data['bindingFields'];
        }

        if (isset($data['wheres'])) {
            $this->wheres = $data['wheres'];
        }

        if (isset($data['name'])) {
            $this->name = $data['name'];
        }
    }

    protected function cacheAction(): array
    {
        if (is_array($this->action['uses'])) {
            if (is_object($this->action['uses'][0])) {
                $this->action['uses'][0] = get_class($this->action['uses'][0]);
            }

            return [
                'type' => 'controller',
                'uses' => implode('@', $this->action['uses'])
            ];
        }

        if ($this->action['uses'] instanceof Closure) {
            $closure = new SerializableClosure($this->action['uses']);

            return [
                'type' => 'serialize',
                'uses' => serialize($closure)
            ];
        }

        return $this->action;
    }
}