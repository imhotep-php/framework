<?php declare(strict_types=1);

namespace Imhotep\Console\Formatter;

enum Color
{
    case default;
    case black;
    case red;
    case redBright;
    case green;
    case greenBright;
    case yellow;
    case yellowBright;
    case blue;
    case blueBright;
    case magenta;
    case magentaBright;
    case cyan;
    case cyanBright;
    case grayBright;
    case gray;
    case white;

    public static function getByName($name, Color $default = null): ?Color
    {
        return match($name) {
            'black' => self::black,
            'white' => self::white,
            'red' => self::red,
            'red-bright', 'redBright' => self::redBright,
            'green' => self::green,
            'green-bright', 'greenBright' => self::greenBright,
            'yellow' => self::yellow,
            'yellow-bright', 'yellowBright' => self::yellowBright,
            'blue' => self::blue,
            'blue-bright', 'blueBright' => self::blueBright,
            'magenta' => self::magenta,
            'magenta-bright', 'magentaBright' => self::magentaBright,
            'cyan' => self::cyan,
            'cyan-bright', 'cyanBright' => self::cyanBright,
            'gray-bright', 'grayBright' => self::grayBright,
            'gray' => self::gray,
            default => $default,
        };
    }

    public function isBright(): bool
    {
        return match($this){
            Color::gray,
            Color::redBright,
            Color::greenBright,
            Color::yellowBright,
            Color::blueBright,
            Color::magentaBright,
            Color::cyanBright,
            Color::white => true,
            default => false,
        };
    }

    public function getCode(): int
    {
        return match($this){
            Color::default => 9,
            Color::black, Color::gray => 0,
            Color::red, Color::redBright => 1,
            Color::green, Color::greenBright => 2,
            Color::yellow, Color::yellowBright => 3,
            Color::blue, Color::blueBright => 4,
            Color::magenta, Color::magentaBright => 5,
            Color::cyan, Color::cyanBright => 6,
            Color::white, Color::grayBright => 7,

        };
    }

    public function getForegroundCode(): string
    {
        return (($this->isBright()) ? '9' : '3').$this->getCode();
    }

    public function getBackgroundCode(): string
    {
        return (($this->isBright()) ? '10' : '4').$this->getCode();
    }
}