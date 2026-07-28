<?php declare(strict_types=1);

namespace Imhotep\Http;

use Closure;
use LogicException;

class StreamedResponse extends Response
{
    protected mixed $callback = null;

    protected bool $streamed = false;

    public function __construct(?callable $callback = null, int $status = 200, array $headers = [])
    {
        parent::__construct(null, $status, $headers);

        if ($callback) {
            $this->setCallback($callback);
        }
    }

    public function setCallback(callable $callback): static
    {
        $this->callback = $callback instanceof Closure ? $callback : $callback(...);
        $this->streamed = false;

        return $this;
    }

    public function callback(): ?Closure
    {
        return $this->callback;
    }

    protected function sendContent(): static
    {
        if ($this->streamed || $this->callback === null) {
            return $this;
        }

        $this->streamed = true;

        if (!isset($this->callback)) {
            throw new LogicException('The Response callback must be set.');
        }

        ($this->callback)();

        return $this;
    }

    public function setContent(mixed $content): static
    {
        if ($content !== null) {
            throw new LogicException('The content cannot be set on a StreamedResponse instance.');
        }

        $this->streamed = true;

        return $this;
    }
}