<?php

declare(strict_types=1);

namespace Imhotep\Console\Output;



class ConsoleOutput extends StreamOutput
{
    protected StreamOutput $stderr;

    protected int $cols = 0;

    protected int $lines = 0;

    protected static ?bool $tput = null;

    protected static ?bool $stty = null;

    public function __construct()
    {
        parent::__construct($this->getOutputStream());

        $this->stderr = new StreamOutput($this->getErrorStream());

        $this->initDimensions();
    }

    protected function initDimensions(): void
    {
        // Windows
        if (DIRECTORY_SEPARATOR === '\\') {
            if (preg_match('/^(\d+)x(\d+)(?: \((\d+)x(\d+)\))?$/', trim(getenv('ANSICON')), $match)) {
                $this->cols = (int)$match[1];
                $this->lines = isset($match[4]) ? (int)$match[4] : (int)$match[2];
                return;
            }
        }

        if ($this->hasTputAvailable()) {
            $this->useTputDimensions();
        }
        elseif ($this->hasSttyAvailable()){
            $this->useSttyDimensions();
        }
    }

    protected function hasTputAvailable(): bool
    {
        if (! is_null(static::$tput)) {
            return static::$tput;
        }

        if (! function_exists('exec')) {
            return false;
        }

        exec("tput 2>&1", $output, $code);

        return static::$tput = ($code === 0);
    }

    protected function useTputDimensions(): void
    {
        $this->cols = (int)exec('tput cols');
        $this->lines = (int)exec('tput lines');
    }

    protected function hasSttyAvailable(): bool
    {
        if (! is_null(static::$stty)) {
            return static::$stty;
        }

        if (! function_exists('exec')) {
            return false;
        }

        exec("stty 2>&1", $output, $code);

        return static::$stty = ($code === 0);
    }

    protected function useSttyDimensions(): void
    {
        $stty = exec('stty -a | grep columns');

        if (preg_match('/rows.(\d+);.columns.(\d+);/i', $stty, $match)) {
            // extract from "rows N; columns N;"
            $this->cols = (int) $match[2];
            $this->lines = (int) $match[1];
        }
        elseif (preg_match('/;.(\d+).rows;.(\d+).columns/i', $stty, $match)) {
            // extract from "; N rows; N columns"
            $this->cols = (int) $match[2];
            $this->lines = (int) $match[1];
        }
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