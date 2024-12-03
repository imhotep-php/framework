<?php declare(strict_types=1);

namespace Imhotep\Console\Formatter\Components;

class BulletList extends Component
{
    protected string $style;

    public function render(string|array $elements, string $marker = '•'): void
    {
        foreach ((array)$elements as $element) {
            $this->output->writeln('  '.$marker.' '.$element);
        }
    }
}