<?php declare(strict_types=1);

namespace Imhotep\Http;

use Imhotep\Routing\Redirector;
use Imhotep\Support\Traits\Macroable;

class ResponseFactory
{
    use Macroable;

    public function __construct(
        protected ?Redirector $redirect = null
    ) {}

    public function make(?string $content = null, int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }

    public function noContent(int $status = 204, array $headers = []): Response
    {
        return $this->make(null, $status, $headers);
    }

    public function text(string $text): Response
    {
        return $this->make($text, 200, ['Content-Type' => 'text/plain']);
    }

    public function xml(string $xml): Response
    {
        return $this->make($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function json(array $data = [], int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }

    public function jsonp(string $callback, array $data = [], int $status = 200, array $headers = []): JsonResponse
    {
        return $this->json($data, $status, $headers)->setCallback($callback);
    }

    public function file(string $file, array $headers = []): FileResponse
    {
        return new FileResponse($file, 200, $headers);
    }

    public function download(string $file, string $name = '', array $headers = [], string $disposition = 'attachment'): FileResponse
    {
        $response = new FileResponse($file, 200, $headers, true, $disposition);

        if ($name) {
            $response->setHeader('Content-Disposition', sprintf('%s; filename="%s"', $disposition, $name));
        }

        return $response;
    }

    public function streamDownload(callable $callback, string $name = '', array $headers = [], string $disposition = 'attachment'): StreamedResponse
    {
        $response = new StreamedResponse($callback, 200, $headers);

        if ($name) {
            $response->setHeader('Content-Disposition', sprintf('%s; filename="%s"', $disposition, $name));
        }

        return $response;
    }

    public function redirect(string $url, int $status = 302, array $headers = []): RedirectResponse
    {
        return new RedirectResponse($url, $status, $headers);
    }

    public function redirectToRoute(string $name, array $parameters = [], int $status = 302, array $headers = []): ?Response
    {
        return $this->redirect?->route($name, $parameters, $status, $headers);
    }
}