<?php declare(strict_types=1);

namespace Imhotep\Contracts\Auth;

use Imhotep\Http\Request;
use Throwable;

class AuthorizationException extends \Exception
{
    /**
     * The HTTP response status code
     * @var int|null
     */
    protected ?int $status = null;

    public function __construct(string $message = "This action is unauthorized.", ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->code = $code ?: 0;
    }

    public function withStatus(?int $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function hasStatus(): bool
    {
        return ! is_null($this->status);
    }
}