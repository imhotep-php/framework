<?php declare(strict_types=1);

namespace Imhotep\Console\Formatter\Components;

use Imhotep\Contracts\Console\Output as OutputContract;
use Imhotep\Support\Stopwatch;

class Task extends Component
{
    public function render(string $string, callable $task = null): void
    {
        $this->output->write($string);

        $stringWidth = $this->output->getFormatter()->getStringLength($string);

        $stopwatch = new Stopwatch(4);

        $result = false;
        try {
            $result = ($task ?: fn() => true)();
        }
        catch (\Throwable $e) {
            throw $e;
        }
        finally {
            $runTime = $task ? ' '.$stopwatch->stop()->last(true).'s' : '';

            $runTimeWidth = $this->output->getFormatter()->getStringLength($runTime);
            $width = 120;
            $dots = max($width - $stringWidth - $runTimeWidth - 5, 0);

            $this->output->write(str_repeat('<fg=gray>.</>', $dots));
            $this->output->write("<fg=gray>{$runTime}</> ");

            if ($result !== false) {
                $this->output->writeln("<fg=green;options=bold>DONE</>");
            }
            else {
                $this->output->writeln("<fg=red;options=bold>FAIL</>");
            }
        }
    }
}