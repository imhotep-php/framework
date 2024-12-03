<?php declare(strict_types=1);

namespace Imhotep\Console\Output;

use Imhotep\Console\Formatter\Formatter;
use Imhotep\Contracts\Console\Output as OutputContract;

abstract class Output implements OutputContract
{
    protected Formatter $formatter;

    public function __construct()
    {
        $this->formatter = new Formatter();
        $this->formatter->setDecorated($this->hasColorSupport());
    }

    public function getFormatter(): Formatter
    {
        return $this->formatter;
    }

    public function setFormatter(Formatter $formatter): static
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function isDecorated(): bool
    {
        return $this->formatter->isDecorated();
    }

    public function setDecorated(bool $decorated): static
    {
        $this->formatter->setDecorated($decorated);

        return $this;
    }

    public function newLine(int $count = 1): static
    {
        $this->doWrite(str_repeat(PHP_EOL, $count));

        return $this;
    }

    public function writeln(iterable|string $messages): static
    {
        return $this->write($messages, true);
    }

    public function write(iterable|string $messages, $newline = false): static
    {
        if (!is_iterable($messages)) {
            $messages = [$messages];
        }

        foreach($messages as $message){
            $message = $this->formatter->format($message);
            $message .= ($newline) ? PHP_EOL : '';
            $this->doWrite($message);
        }

        return $this;
    }

    abstract protected function doWrite(string $message): void;

    abstract protected function hasColorSupport(): bool;
}