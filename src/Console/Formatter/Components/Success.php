<?php

declare(strict_types=1);

namespace Imhotep\Console\Formatter\Components;

class Success extends Component
{
    public function render($string)
    {
        $this->output->newLine();
        $this->output->write("<bg=green;fg=white;options=dim> OK </> {$string}");
        $this->output->newLine();
    }
}