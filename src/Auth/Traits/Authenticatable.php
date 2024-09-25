<?php declare(strict_types=1);

namespace Imhotep\Auth\Traits;

trait Authenticatable
{
    protected string $rememberToken = 'remember_secret';

    public function getAuthIdName(): string
    {
        return 'id';
    }

    public function getAuthId(): mixed
    {
        return $this->attributes[$this->getAuthIdName()];
    }

    public function getAuthPassword(): string
    {
        return $this->attributes['password'];
    }

    public function getRememberToken(): ?string
    {
        return $this->attributes[$this->rememberToken];
    }

    public function setRememberToken(string $value): void
    {
        $this->attributes[$this->rememberToken] = $value;
    }

    public function getRememberTokenName(): string
    {
        return $this->rememberToken;
    }

    public function setRememberTokenName(string $name): void
    {
        $this->rememberToken = $name;
    }
}