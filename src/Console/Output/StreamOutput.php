<?php

declare(strict_types=1);

namespace Imhotep\Console\Output;

use Imhotep\Console\Formatter\Formatter;
use Imhotep\Contracts\Console\Output as OutputContract;

class StreamOutput implements OutputContract
{
    protected mixed $stream;

    protected Formatter $formatter;

    public function __construct($stream)
    {
        $this->stream = $stream;

        $this->formatter = new Formatter();
        $this->formatter->setDecorated($this->hasColorSupport());
    }

    public function getFormatter(): Formatter
    {
        return $this->formatter;
    }

    public function newLine(): void
    {
        $this->doWrite(PHP_EOL);
    }

    public function writeln(string|iterable $messages): void
    {
        $this->write($messages, true);
    }

    public function write(string|iterable $messages, $newline = false): void
    {
        if (!is_iterable($messages)) {
            $messages = [$messages];
        }

        foreach($messages as $message){
            $message = $this->formatter->format($message);
            $message .= ($newline) ? PHP_EOL : '';
            $this->doWrite($message);
        }
    }

    protected function doWrite($data)
    {
        @fwrite($this->stream, $data);
        @fflush($this->stream);
    }

    protected function hasColorSupport(): bool
    {
        return stream_isatty($this->stream);
    }

}