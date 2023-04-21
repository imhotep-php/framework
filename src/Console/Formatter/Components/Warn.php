<?php

declare(strict_types=1);

namespace Imhotep\Console\Formatter\Components;

class Warn extends Component
{
    public function render($string)
    {
        $this->output->newLine();
        $this->output->write("<bg=yellow;fg=white;options=dim> WARN </> {$string}");
        $this->output->newLine();
    }
}