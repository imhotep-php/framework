<?php

namespace Imhotep\Contracts\Routing;

use Imhotep\Contracts\Http\Request;

interface RouteCollection
{
    public function add(Route $route): static;

    public function match(Request $request): ?Route;

    public function getByName(string $name): ?Route;

    public function getByAction(string|array $action): ?Route;

    public function getRoutes(): array;
}