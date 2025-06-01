<?php declare(strict_types=1);

namespace Imhotep\Http\Testing;

use Imhotep\Http\UploadedFile;

class File extends UploadedFile
{
    public function __construct(
        public string $name,
        public mixed $stream,
        public int $size = 0,
        public string $mimeType = ''
    )
    {
        $path = stream_get_meta_data($stream)['uri'];

        parent::__construct($path, $name, $mimeType, $size, 0, true);
    }

    public static function create(string $name, int|string $size): File
    {
        return FileFactory::create($name, $size);
    }

    public static function createWithContent(string $name, mixed $content): File
    {
        return FileFactory::createWithContent($name, $content);
    }

    public static function createImage(string $name, int $width = 10, int $height = 10): File
    {
        return FileFactory::createImage($name, $width, $height);
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function setMimeType(string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }
}