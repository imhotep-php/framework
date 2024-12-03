<?php declare(strict_types=1);

namespace Imhotep\Console\Formatter\Components;

use Imhotep\Console\Formatter\Color;
use Imhotep\Support\Str;

class Alert extends Component
{
    protected Color $color;

    public function render(string $text, string $color = 'yellow'): void
    {
        $this->color = Color::getByName($color, Color::yellow);

        $paddingWidth = 4;

        $width = max($this->output->width(), 120);
        $textWidth = Str::width($text);

        $contentWidth = $textWidth + ($paddingWidth * 2) + 2;
        if ($contentWidth < $width) {
            $width = $contentWidth;
        }

        $border = str_repeat('*', $width);
        $padding = str_repeat(' ', $paddingWidth);
        $content = "*".$padding.$text.$padding."*";

        $this->line($border);
        $this->line($content);
        $this->line($border);

        $this->output->newLine();
    }

    protected function line(string $line): void
    {
        $this->output->writeln('<fg='.$this->color->name.'>'.$line.'</>');
    }
}