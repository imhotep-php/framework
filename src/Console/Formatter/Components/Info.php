<?php

declare(strict_types=1);

namespace Imhotep\Console\Formatter\Components;

class Info extends Component
{
    public function render($string)
    {
        $this->output->newLine();
        $this->output->write("<bg=blue;fg=white;options=dim> INFO </> {$string}");
        $this->output->newLine();
    }
}