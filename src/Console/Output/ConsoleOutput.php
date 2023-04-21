<?php

declare(strict_types=1);

namespace Imhotep\Console\Output;



class ConsoleOutput extends StreamOutput
{
    protected StreamOutput $stderr;

    protected int $cols = 0;

    protected int $lines = 0;

    public function __construct()
    {
        parent::__construct($this->getOutputStream());

        $this->stderr = new StreamOutput($this->getErrorStream());

        $this->cols = (int)exec('tput cols');
        $this->lines = (int)exec('tput lines');
    }

    protected function getOutputStream(): mixed
    {
        if(defined('STDOUT')){
            return STDOUT;
        }

        return @fopen('php://stdout', 'w') ?: fopen('php://output', 'w');
    }

    protected function getErrorStream(): mixed
    {
        if(defined('STDERR')){
            return STDERR;
        }

        return @fopen('php://stderr', 'w') ?: fopen('php://output', 'w');
    }

    public function getErrorOutput(): StreamOutput
    {
        return $this->stderr;
    }

    public function getCols(): int
    {
        return $this->cols;
    }

    public function getLines(): int
    {
        return $this->lines;
    }
}