<?php declare(strict_types=1);

namespace Imhotep\Http;

use BadMethodCallException;
use Imhotep\Container\Container;
use Imhotep\Support\MimeTypes;
use Imhotep\Support\Str;
use Imhotep\Support\Traits\DeprecatedGetters;
use SplFileInfo;

class UploadedFile extends SplFileInfo
{
    use DeprecatedGetters;

    protected ?string $hashName = null;

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

    public function hashName(): string
    {
        return $this->hashName ?: $this->hashName = Str::random(24);
    }

    public function originalPath(): string
    {
        return $this->path;
    }

    public function originalName(): string
    {
        return $this->name;
    }

    public function originalExtension(): string
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    public function originalMimeType(): string
    {
        return $this->mimeType;
    }

    public function originalSize(): int
    {
        return $this->size;
    }

    public function path(): false|string
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

    public function size(): int|false
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

        return ($this->error === UPLOAD_ERR_OK && $this->isUploaded() && $this->size());
    }

    public function store(string $path, array|string|null $options = null): bool|string
    {
        $name = $this->hashName();

        if ($extension = $this->extension()) {
            $name = $name.'.'.$extension;
        }

        return $this->storeAs($path, $name, $options);
    }

    public function storeAs(string $path, string $name, array|string|null $options = null): string|false
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

    public function __call(string $method, array $parameters): mixed
    {
        if ($result = $this->deprecatedGettersCall($method, $parameters)) {
            return $result;
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}