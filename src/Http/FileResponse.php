<?php declare(strict_types=1);

namespace Imhotep\Http;

use Closure;
use DateTimeImmutable;
use Imhotep\Contracts\Http\Request as RequestContract;
use Imhotep\Support\MimeTypes;
use InvalidArgumentException;
use SplFileInfo;
use SplFileObject;

class FileResponse extends Response
{
    protected SplFileInfo $file;

    //protected string $disposition = 'attachment';

    protected ?string $filename = null;

    protected int $offset = 0;

    protected int $maxlen = 0;

    protected int $chunkSize = 16 * 1024;

    protected bool $deleteFileAfterSend = false;

    public function __construct(
        SplFileInfo|string $file,
        int $status = 200,
        array $headers = [],
        bool $public = true,
        ?string $disposition = null,
        bool $autoEtag = false,
        bool $autoLastModified = true
    )
    {
        parent::__construct(null, $status, $headers);

        $this->setFile($file, $disposition, $autoEtag, $autoLastModified);

        if ($public) {
            $this->setPublicCache();
        } else {
            $this->setPrivateCache();
        }
    }

    public function setFile(SplFileInfo|string $file, ?string $disposition = null, bool $autoEtag = false, bool $autoLastModified = true): static
    {
        if (! $file instanceof SplFileInfo) {
            $file = new SplFileInfo($file);
        }

        if (! $file->isFile()) {
            throw new InvalidArgumentException(sprintf('The file "%s" does not exist.', $file->getPathname()));
        }

        if (! $file->isReadable()) {
            throw new InvalidArgumentException(sprintf('The file "%s" is not readable.', $file->getPathname()));
        }

        $this->file = $file;

        if ($autoEtag) {
            $this->setAutoEtag();
        }

        if ($autoLastModified) {
            $this->setAutoLastModified();
        }

        return $this;
    }

    protected function setAutoLastModified(): static
    {
        $datetime = $this->file->getMTime() ? $this->file->getMTime() : time();

        $this->setLastModified(DateTimeImmutable::createFromFormat('U', (string)$datetime));

        return $this;
    }

    protected function setAutoEtag(): static
    {
        $this->setEtag(base64_encode(hash_file('xxh128', $this->file->getPathname(), true)));

        return $this;
    }

    public function setPublicCache(): static
    {
        $this->setCacheControl('public');

        return $this;
    }

    public function setPrivateCache(): static
    {
        $this->setCacheControl('private');

        return $this;
    }

    public function deleteFileAfterSend(bool $shouldDelete = true): static
    {
        $this->deleteFileAfterSend = $shouldDelete;

        return $this;
    }

    public function prepare(RequestContract $request): static
    {
        parent::prepare($request);

        if ($this->isInformational() || $this->isEmpty()) {
            $this->maxlen = 0;

            return $this;
        }

        if ($this->headers->missing('Content-Type')) {
            $mimeType = MimeTypes::guessMimeType($this->file->getPathname());

            $this->headers->set('Content-Type', $mimeType ?: 'application/octet-stream');
        }

        if (($fileSize = $this->file->getSize()) === false) {
            return $this;
        }

        $hasRange = ($range = $this->headers->get('Range'))
            && $request->isMethod('GET')
            && $this->hasValidIfRangeHeader($this->headers->get('If-Range'));

        if ($hasRange) {
            $this->prepareRange($range, $fileSize);
        }
        else {
            $this->offset = 0;
            $this->maxlen = -1;
        }

        if ($request->isMethod('HEAD')) {
            $this->maxlen = 0;
        }

        return $this;
    }

    protected function prepareRange(string $range, int $fileSize): void
    {
        $ranges = explode(',', substr($range, 6));

        if (count($ranges) > 1) {
            return;
        }

        [$start, $end] = explode('-', $ranges[0]);

        $start = (int)$start;
        $end = trim($end) === '' ? $fileSize - 1 : (int)$end;
        $end = min($end, $fileSize);

        if ($start > $end || $end >= $fileSize) {
            $this->setHeader('Content-Range', sprintf('bytes */%s', $fileSize));
            $this->setStatus(416); // Range Not Satisfiable
            return;
        }

        $this->offset = $start;
        $this->maxlen = $end - $start + 1;

        $this->setStatus(206); // Partial Content
        $this->setHeader('Content-Length', (string)$this->maxlen);
        $this->setHeader('Content-Range', sprintf('bytes %s-%s/%s', $start, $end, $fileSize));
    }

    protected function hasValidIfRangeHeader(?string $header): bool
    {
        if (is_null($header)) {
            return true;
        }

        if ($this->etag() === $header) {
            return true;
        }

        if (null === $lastModified = $this->lastModified()) {
            return false;
        }

        return $lastModified === $header;
    }

    public function sendContent(): static
    {
        try {
            if (!$this->isSuccessful()) {
                return $this;
            }

            if ($this->maxlen === 0) {
                return $this;
            }

            ignore_user_abort(true);

            $out = fopen('php://output', 'w');
            $file = new SplFileObject($this->file->getPathname(), 'r');

            if ($this->offset > 0) {
                $file->fseek($this->offset);
            }

            $length = $this->maxlen;
            while ($length && !$file->eof()) {
                $read = $length > $this->chunkSize || $length === -1 ? $this->chunkSize : $length;

                if (($data = $file->fread($read)) === false) {
                    break;
                }

                while ($data !== '') {
                    $read = fwrite($out, $data);
                    if ($read === false || connection_aborted()) {
                        break 2;
                    }
                    if ($length > 0) {
                        $length -= $read;
                    }
                    $data = substr($data, $read);
                }
            }
        }
        finally {
            if ($this->deleteFileAfterSend && is_file($this->file->getPathname())) {
                unlink($this->file->getPathname());
            }
        }

        return $this;
    }
}