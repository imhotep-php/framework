<?php declare(strict_types=1);

namespace Imhotep\Http;

use InvalidArgumentException;

class JsonResponse extends Response
{
    protected int $options = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    protected string $json = '';

    protected ?string $callback = null;

    public function __construct(mixed $content = [], int $status = 200, array $headers = [], bool $json = false)
    {
        parent::__construct(null, $status, $headers);

        if (is_string($content) && $json) {
            $this->setJson($content);
        } else {
            $this->setData($content);
        }
    }

    public function json(): string
    {
        return $this->json;
    }

    public function setJson(string $json): static
    {
        $this->original = $json;
        $this->json = $json;

        $this->update();

        return $this;
    }

    public function setData(mixed $data): static
    {
        $this->original = $data;

        $json = json_encode($data, $this->options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(json_last_error_msg());
        }

        $this->json = $json;
        $this->update();

        return $this;
    }

    public function options(): int
    {
        return $this->options;
    }

    public function setOptions(int $options): static
    {
        $this->options = $options;

        if ($this->json !== '') {
            $this->setData(json_decode($this->json, true));
        }

        return $this;
    }

    public function setCallback(?string $callback, bool $force = false): static
    {
        if (is_string($callback)) {
            if ($force === false) {
                $this->checkCallback($callback);
            }

            $this->callback = $callback;
        }

        $this->update();

        return $this;
    }

    protected function checkCallback(string $callback): void
    {
        $pattern = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*(?:\[(?:"(?:\\\.|[^"\\\])*"|\'(?:\\\.|[^\'\\\])*\'|\d+)\])*?$/u';
        $reserved = [
            'break', 'do', 'instanceof', 'typeof', 'case', 'else', 'new', 'var', 'catch', 'finally', 'return', 'void', 'continue', 'for', 'switch', 'while',
            'debugger', 'function', 'this', 'with', 'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum', 'extends', 'super',  'const', 'export',
            'import', 'implements', 'let', 'private', 'public', 'yield', 'interface', 'package', 'protected', 'static', 'null', 'true', 'false',
        ];
        $parts = explode('.', $callback);

        foreach ($parts as $part) {
            if (!preg_match($pattern, $part) || in_array($part, $reserved, true)) {
                throw new InvalidArgumentException('The callback name is not valid.');
            }
        }
    }

    protected function update(): void
    {
        if ($this->callback) {
            $this->headers->set('Content-Type', 'text/javascript');

            $this->content = sprintf('/**/%s(%s);', $this->callback, $this->json);

            return;
        }

        $this->headers->set('Content-Type', 'application/json');
        $this->content = $this->json;
    }
}