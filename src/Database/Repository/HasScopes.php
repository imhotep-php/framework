<?php declare(strict_types=1);

namespace Imhotep\Database\Repository;

use Closure;
use Imhotep\Database\Query\Builder;
use InvalidArgumentException;

trait HasScopes
{
    protected array $scopes = [];

    protected static array $globalScopes = [];

    public static function addGlobalScope(string|Closure|Scope $scope, Closure|Scope|null $implementation = null): void
    {
        if (! is_string($scope)) {
            $scopeKey = $scope instanceof Closure ? spl_object_hash($scope) : get_class($scope);

            static::$globalScopes[static::class][$scopeKey] = $scope;

            return;
        }

        if (is_null($implementation)) {
            if (! (class_exists($scope) && is_subclass_of($scope, Scope::class)) ) {
                throw new InvalidArgumentException(
                    'Global scope must be a Closure or class extending '.Scope::class
                );
            }

            $implementation = new $scope;
        }

        static::$globalScopes[static::class][$scope] = $implementation;
    }

    public static function addGlobalScopes(array $scopes): void
    {
        foreach ($scopes as $key => $scope) {
            is_string($key)
                ? static::addGlobalScope($key, $scope)
                : static::addGlobalScope($scope);
        }
    }

    public static function hasGlobalScope(string|Scope $scope): bool
    {
        return ! is_null(static::getGlobalScope($scope));
    }

    public static function getGlobalScope(string|Scope $scope): mixed
    {
        $scopeKey = is_string($scope) ? $scope : get_class($scope);

        return static::$globalScopes[static::class][$scopeKey] ?? null;
    }

    public static function getGlobalScopes(): array
    {
        return static::$globalScopes[static::class] ?? [];
    }

    public static function getAllGlobalScopes(): array
    {
        return static::$globalScopes;
    }

    public static function setAllGlobalScopes(array $scopes): void
    {
        static::$globalScopes = $scopes;
    }

    public function scope(callable $scope): static
    {
        $this->scopes[] = $scope;

        return $this;
    }

    protected function applyScopes(Builder $query): void
    {
        foreach ($this->scopes as $scope) {
            $scope($query);
        }

        $this->scopes = [];
    }






    // Examples
    protected function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    protected function scopeRead(Builder $query): void
    {
        $query->whereNotNull('read_at');
    }

    protected function scopeUnread(Builder $query): void
    {
        $query->whereNull('read_at');
    }

    protected function scopeRole(Builder $query, string $role): void
    {
        $query->where('role', $role);
    }
}