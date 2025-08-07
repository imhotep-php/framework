<?php

namespace Imhotep\Debug;

use Imhotep\Debug\Dumper\IDumper;
use Imhotep\Debug\Dumper\CliDumper;
use Imhotep\Debug\Dumper\HtmlDumper;
use Imhotep\Debug\Cloner\ICloner;
use Imhotep\Debug\Cloner\Cloner;

class VarDumper
{
    protected IDumper $dumper;

    protected ICloner $cloner;

    public function __construct(?IDumper $dumper = null, ?ICloner $cloner = null)
    {
        if (is_null($dumper)) {
            $dumper = in_array(PHP_SAPI, ['cli', 'phpdbg']) ?
                new CliDumper() : new HtmlDumper();
        }

        $this->dumper = $dumper;

        if (is_null($cloner)) {
            $cloner = new Cloner();
        }

        $this->cloner = $cloner;
    }

    public function debug($var)
    {
        return $this->dumper->dump($this->cloner->cloneVar($var));
    }

    public static function dump($var): void
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        static::$instance->debug($var);
    }



    protected static VarDumper|null $instance = null;

    public static function getInstance(): static
    {
        return static::$instance ??= new static();
    }

    public static function setInstance(?VarDumper $instance = null): void
    {
        static::$instance = $instance;
    }
}