<?php

declare(strict_types=1);

namespace Imhotep\Console\Formatter\Components;

class TwoColumnDetail extends Component
{
    public function render(string $first, string $second)
    {
        $cols = $this->output->getCols();
        if ($cols > 120) $cols = 120;

        $firstLength = $this->output->getFormatter()->getStringLength($first);
        $secondLength = $this->output->getFormatter()->getStringLength($second);
        $dotLength = $cols - $firstLength - $secondLength - 2;

        $this->output->write($first);

        if ($dotLength > 0) {
            $this->output->write(sprintf("<fg=gray> %s </>", str_repeat(".", $dotLength)));
        }

        $this->output->write($second);

        $this->output->newLine();
    }
}