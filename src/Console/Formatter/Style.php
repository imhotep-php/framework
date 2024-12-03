<?php declare(strict_types=1);

namespace Imhotep\Console\Formatter;

class Style
{
    public function __construct(
        protected Color|null $foreground = null,
        protected Color|null $background = null,
        protected array $options = [],
    ) { }

    public function setForeground(Color|null $foreground): void
    {
        $this->foreground = $foreground;
    }

    public function setBackground(Color|null $background): void
    {
        $this->background = $background;
    }

    public function setOptions(array $options): void
    {
        foreach($options as $option){
            if($option instanceof Option){
                $this->setOption($option);
            }
        }
    }

    public function setOption(Option $option): void
    {
        $isMatch = false;
        foreach($this->options as $opt){
            if($opt === $option) $isMatch = true;
        }

        if($isMatch === false){
            $this->options[] = $option;
        }
    }

    public function apply(string $text): string
    {
        return $this->beginCode() . $text . $this->endCode();
    }

    private function beginCode(): string
    {
        $codes = [];
        if($this->foreground){ // 39
            $codes[] = $this->foreground->getForegroundCode();
        }
        if($this->background){ // 40
            $codes[] = $this->background->getBackgroundCode();
        }
        foreach ($this->options as $option) {
            $codes[] = $option->getBeginCode();
        }

        if (empty($codes)) return '';

        return sprintf("\e[%sm", implode(";", $codes));
    }

    private function endCode(): string
    {
        $codes = [];
        if($this->foreground){
            $codes[] = 39;
        }
        if($this->background){
            $codes[] = 49;
        }
        foreach ($this->options as $option) {
            $codes[] = $option->getEndCode();
        }

        if (empty($codes)) return '';

        return sprintf("\e[%sm", implode(";", $codes));
    }
}