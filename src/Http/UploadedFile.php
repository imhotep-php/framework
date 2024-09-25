<?php

declare(strict_types=1);

namespace Imhotep\Http;

use Imhotep\Container\Container;
use Imhotep\Support\MimeTypes;
use Imhotep\Support\Str;

class UploadedFile extends \SplFileInfo
{
    public static function createFrom(array $file, bool $test = false): ?static
    {
        // Если в массиве отсутствуют обязательные поля
        // или содержат недопустимый тип, исключаем файл из запроса
        foreach (['tmp_name', 'name', 'type'] as $key) {
            if (! isset($file[$key]) || ! is_string($file[$key])) return null;
        }

        foreach (['size', 'error'] as $key) {
            if (! isset($file[$key]) || ! is_integer($file[$key])) return null;
        }

        return new static($file['tmp_name'], $file['name'], $file['type'], $file['size'], $file['error'], $test);
    }

    public function __construct(
        protected string $path,
        protected string $name,
        protected string $mimeType,
        protected int $size,
        protected int $error,
        protected bool $test = false,
    )
    {
        parent::__construct($path);
    }

    public function originalName(): string
    {
        return $this->name;
    }

    public function originalExtension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function originalMimeType(): string
    {
        return $this->mimeType;
    }

    public function originalSize(): int
    {
        return $this->size;
    }

    public function path(): string
    {
        return $this->getRealPath();
    }

    public function mimeType(): ?string
    {
        return MimeTypes::guessMimeType($this->path);
    }

    public function name(): string
    {
        return $this->getBasename();
    }

    public function getSize(): int|false
    {
        if ($this->test) {
            return $this->size;
        }

        return parent::getSize();
    }

    public function extension(): ?string
    {
        return MimeTypes::getExtension($this->mimeType() ?? '') ?? $this->originalExtension();
    }

    public function isUploaded(): bool
    {
        return is_uploaded_file($this->path);
    }

    public function isValid(): bool
    {
        if ($this->test) {
            return $this->error === UPLOAD_ERR_OK;
        }

        return ($this->error === UPLOAD_ERR_OK && $this->isUploaded() && $this->getSize());
    }

    public function isValidImage(): bool
    {
        $isValid = $this->isValid();

        if ($this->test) {
            return $isValid;
        }

        if (exif_imagetype($this->path) === false) {
            return false;
        }

        list($width, $height) = getimagesize($this->path);

        if ($width > 0 && $height > 0) {
            return true;
        }

        return false;
    }

    public function store(string $path, string|array $options = null): bool|string
    {
        $name = $this->hashName;

        if ($extension = $this->extension()) {
            $name = $name.'.'.$extension;
        }

        dd($name);

        return $this->storeAs($path, $name, $options);
    }

    public function storeAs(string $path, string $name, string $options = null): string|false
    {
        if( is_string($options)) {
            $options = ['disk' => $options];
        }

        $disk = $options['disk'] ?? null;
        unset($options['disk']);

        $fs = Container::getInstance()->make('filesystem')->disk($disk);

        $fs->ensureDirectoryExists($path);

        return $fs->putFileAs($path, $this->path, $name, $options ?? []);
    }

    protected ?string $hashName = null;

    public function hashName(): string
    {
        return $this->hashName ?: $this->hashName = Str::random(24);
    }
}