<?php declare(strict_types=1);

namespace Imhotep\Console\Formatter;

enum Option : int
{
    case bold = 1;
    case dim = 2;
    case italic = 3;
    case underline = 4;
    case blink = 5;
    case reverse = 7;
    case hidden = 8;

    public static function getByName(string $name): Option|null
    {
        return match($name){
            'bold' => self::bold,
            'dim' => self::dim,
            'italic' => self::italic,
            'underline' => self::underline,
            'blink' => self::blink,
            'reverse' => self::reverse,
            'hidden' => self::hidden,
            default => null,
        };
    }

    public function getBeginCode(): int
    {
        return $this->value;
    }

    public function getEndCode(): int
    {
        if ($this->value === 1) return 22;

        return $this->value + 20;
    }
}