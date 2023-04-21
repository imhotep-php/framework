<?php

declare(strict_types=1);

namespace Imhotep\Console\Formatter\Components;

class Error extends Component
{
    public function render($string)
    {
        $this->output->newLine();
        $this->output->write("<bg=red;fg=white;options=dim> ERROR </> {$string}");
        $this->output->newLine();
    }
}