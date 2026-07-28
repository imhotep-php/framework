<?php declare(strict_types=1);

namespace Imhotep\Http;

use ArrayObject;
use DateTimeInterface;
use DateTimeZone;
use Imhotep\Contracts\Arrayable;
use Imhotep\Contracts\Http\Request as RequestContract;
use Imhotep\Contracts\Http\Response as ResponseContract;
use Imhotep\Contracts\Jsonable;
use Imhotep\Contracts\Renderable;
use Imhotep\Http\Traits\HasCacheControl;
use Imhotep\Http\Traits\HasCookies;
use Imhotep\Http\Traits\HasHeaders;
use Imhotep\Http\Traits\HasHttpStatus;
use Imhotep\Support\Str;
use Imhotep\Support\Traits\DeprecatedGetters;
use Imhotep\Support\Traits\Macroable;
use InvalidArgumentException;
use JsonSerializable;

class Response implements ResponseContract
{
    use HasHttpStatus, HasHeaders, HasCacheControl, HasCookies, DeprecatedGetters, Macroable {
        __call as macroCall;
    }

    protected string $protocol = '1.1';

    protected string $charset = 'UTF-8';

    protected mixed $original = null;

    protected ?string $content = null;

    public function __construct(mixed $content = null, int $status = 200, array $headers = [])
    {
        $this->headers = new HeaderBag($headers);

        $this->setContent($content);
        $this->setStatus($status);
    }

    public function protocol(): string
    {
        return $this->protocol;
    }

    public function setProtocol(string $protocol): static
    {
        $this->protocol = $protocol;

        return $this;
    }

    public function charset(): string
    {
        return $this->charset;
    }

    public function setCharset(string $charset): static
    {
        $this->charset = strtoupper($charset);

        return $this;
    }

    public function originalContent(): mixed
    {
        return $this->original instanceof self
            ? $this->original->{__FUNCTION__}()
            : $this->original;
    }

    public function content(): ?string
    {
        return $this->content;
    }

    public function setContent(mixed $content): static
    {
        $this->original = $content;

        if (is_null($content) || is_string($content)) {
            $this->content = $content;

            return $this;
        }

        if (is_scalar($content)) {
            $this->content = (string)$content;

            return $this;
        }

        if ($this->isJsonContent($content)) {
            $this->setHeader('Content-Type', 'application/json');

            $content = $this->jsonEncode($content);

            if ($content === false) {
                throw new InvalidArgumentException(json_last_error_msg());
            }
        }

        elseif ($content instanceof Renderable) {
            $content = $content->render();
        }

        elseif ($content instanceof static) {
            $content = $content->content();
        }

        $this->content = $content;

        return $this;
    }

    protected function isJsonContent(mixed $content): bool
    {
        return $content instanceof Arrayable ||
            $content instanceof Jsonable ||
            $content instanceof ArrayObject ||
            $content instanceof JsonSerializable ||
            is_array($content);
    }

    protected function jsonEncode(mixed $content): false|string
    {
        if ($content instanceof Jsonable) {
            return $content->toJson();
        }

        $data = $content instanceof Arrayable ? $content->toArray() : $content;
        $result = json_encode($data);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(json_last_error_msg());
        }

        return $result;
    }


    public function contentType(): ?string
    {
        if ($value = $this->headers->get('Content-Type')) {
            if (str_contains($value, ';')) {
                $exploded = explode(';', $value);
                return trim($exploded[0]);
            }

            return $value;
        }

        return null;
    }

    public function setContentType(string $contentType, ?string $charset = null): static
    {
        $this->headers->set('Content-Type', $contentType);

        if ($charset !== null) {
            $this->setCharset($charset);
        }

        return $this;
    }

    public function expires(): ?DateTimeInterface
    {
        return $this->headers->getDate('Expires');
    }

    public function setExpires(DateTimeInterface $date): static
    {
        $date = $date->setTimezone(new DateTimeZone('UTC'));

        $this->headers->set('Expires', $date->format('D, d M Y H:i:s').' GMT');

        return $this;
    }

    public function lastModified(): DateTimeInterface
    {
        return $this->headers->getDate('Last-Modified');
    }

    public function setLastModified(DateTimeInterface $date): static
    {
        $date = $date->setTimezone(new DateTimeZone('UTC'));

        $this->headers->set('Last-Modified', $date->format('D, d M Y H:i:s').' GMT');

        return $this;
    }

    public function etag(): ?string
    {
        return $this->headers->get('ETag');
    }

    public function setEtag(?string $etag = null): static
    {
        $this->headers->set('ETag', sprintf('"%s"', $etag));

        return $this;
    }



    public function prepare(RequestContract $request): static
    {
        if ($request->isMethod('HEAD')) {
            $this->setContent(null);
        }

        return $this;
    }

    public function send(): static
    {
        $this->sendHeaders()->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }
        elseif (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            static::closeOutputBuffers(0, true);
        }

        return $this;
    }

    protected function sendHeaders(): static
    {
        if (headers_sent()) {
            return $this;
        }

        foreach($this->headers->all() as $name => $value){
            $name = $this->headers->normalizeName($name);

            if ($name === 'Content-Type') {
                header($name.': '.$value.'; charset='.$this->charset, false, $this->statusCode);
            }
            else{
                header($name.': '.$value, false, $this->statusCode);
            }
        }

        foreach ($this->cookies as $cookie) {
            header("Set-Cookie: ".(string)$cookie, false, $this->statusCode);
        }

        header(sprintf('HTTP/%s %s %s', $this->protocol, $this->statusCode, $this->statusPhrase), true, $this->statusCode);

        return $this;
    }

    protected function sendContent(): static
    {
        echo $this->content ?? '';

        return $this;
    }

    public static function closeOutputBuffers(int $targetLevel, bool $flush): void
    {
        $status = ob_get_status(true);
        $level = count($status);
        $flags = PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE);

        while ($level-- > $targetLevel && ($s = $status[$level]) && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])) {
            if ($flush) {
                ob_end_flush();
                flush();
            } else {
                ob_end_clean();
            }
        }
    }

    public function __call(string $method, array $parameters): mixed
    {
        if ($result = $this->deprecatedGettersCall($method, $parameters)) {
            return $result;
        }

        return $this->macroCall($method, $parameters);
    }
}