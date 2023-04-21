<?php

namespace Imhotep\SimpleS3;

class S3Response
{
    protected ?array $error = null;

    protected ?int $code = null;

    protected string $contentType;

    protected array $headers = [];

    protected mixed $body = null;

    protected mixed $data;

    public function __construct()
    {

    }

    public function saveToResource($resource): void
    {
        $this->body = $resource;
    }

    public function __curlWriteFunction($ch, $data): false|int
    {
        if (is_resource($this->body)) {
            return fwrite($this->body, $data);
        }

        return strlen($this->body .= $data);
    }

    public function __curlHeaderFunction($ch, $data): int
    {
        $header = explode(':', $data);

        if (count($header) === 2) {
            list($key, $value) = $header;
            $this->headers[$key] = trim($value);
            if ($key === 'etag') {
                $this->headers[$key] = trim($this->headers[$key], '"');
            }
        }

        return strlen($data);
    }

    public function finalize($ch): void
    {
        $this->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        if (is_resource($this->body)) {
            rewind($this->body);
        }

        if (curl_errno($ch) || curl_error($ch)) {
            $this->error = [
                'code' => curl_errno($ch),
                'message' => curl_error($ch),
            ];
        }
        elseif (str_contains($this->contentType, 'application/xml') && ! empty($this->body) && is_string($this->body)) {
            $parser = new XMLParser($this->body);

            if ($parser->isError()) {
                $this->error = $parser->getData();
            }
            else {
                $this->data = $parser->getData();
            }
        }
        else {
            $this->data = $this->body;
        }
    }

    public function getResult(): S3Result
    {
        return new S3Result($this->getMeta(), $this->getData(), $this->getError());
    }

    protected function getMeta(): array
    {
        return [
            'statusCode' => $this->code,
            'headers' => $this->headers,
        ];
    }

    protected function getData(): mixed
    {
        return $this->data;
    }

    protected function getError(): array
    {
        return $this->error ?? [];
    }
}