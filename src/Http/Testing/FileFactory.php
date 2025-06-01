<?php declare(strict_types=1);

namespace Imhotep\Http\Testing;

use LogicException;

class FileFactory
{
    public static function create(string $name, int|string $size, string $mimeType = ''): File
    {
        return new File($name, tmpfile(), static::formatSize($size), $mimeType);
    }

    public static function createWithContent(string $name, mixed $content): File
    {
        fwrite($tmpfile = tmpfile(), $content);

        return new File($name, $tmpfile, fstat($tmpfile)['size']);
    }

    public static function createImage(string $name, int $width = 10, int $height = 10): File
    {
        return new File($name, static::makeImage(
            $width, $height, pathinfo($name, PATHINFO_EXTENSION)
        ));
    }

    protected static function makeImage(int $width, int $height, string $extension): mixed
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new LogicException('GD extension is not installed.');
        }

        if ($extension === 'jpg') {
            $extension = 'jpeg';
        }

        $imageFunction = 'image'.$extension;

        if (! function_exists($imageFunction)) {
            throw new LogicException($imageFunction.' function is not defined and image cannot be generated.');
        }

        call_user_func(
            $imageFunction,
            imagecreatetruecolor($width, $height),
            $tmpfile = tmpfile()
        );

        return $tmpfile;
    }

    protected static function formatSize(int|string $size): int
    {
        if (is_int($size)) {
            return $size;
        }

        if (! preg_match("/^(?<number>((\d+)?\.)?\d+)(?<suffix>(B|K|M|G|T|P)B?)?$/i", strtoupper($size), $match)) {
            throw new \InvalidArgumentException("Size [$size] is not valid format");
        }

        $number = (float)$match['number'];
        $suffix = strtoupper($match['suffix'] ?? '');

        $exponent = match($suffix) {
            'K', 'KB' => 1,
            'M', 'MB' => 2,
            'G', 'GB' => 3,
            'T', 'TB' => 4,
            'P', 'PB' => 5,
            default => 0,
        };

        return intval($number * (1024 ** $exponent));
    }
}