<?php declare(strict_types=1);

namespace Imhotep\Contracts\Auth;

class AuthenticationException extends \Exception
{
    protected array $guards;

    protected ?string $redirectTo;

    public function __construct(string $message = "", array $gurads = [], string $redirectTo = null)
    {
        parent::__construct($message);

        $this->guards = $gurads;
        $this->redirectTo = $redirectTo;
    }

    public function guards(): array
    {
        return $this->guards;
    }

    public function redirectTo(): ?string
    {
        return $this->redirectTo;
    }
}