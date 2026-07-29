<?php declare(strict_types=1);

namespace Imhotep\Facades;

use Imhotep\Http\FileResponse;
use Imhotep\Http\JsonResponse;
use Imhotep\Http\RedirectResponse;
use Imhotep\Http\Response as BaseResponse;
use Imhotep\Http\ResponseFactory;
use Imhotep\Http\StreamedResponse;

/**
 * @method static BaseResponse make(?string $content = null, int $status = 200, array $headers = [])
 * @method static BaseResponse noContent(int $status = 204, array $headers = [])
 * @method static BaseResponse text(string $text)
 * @method static BaseResponse xml(string $xml)
 * @method static JsonResponse json($data = [], $status = 200, array $headers = [])
 * @method static JsonResponse jsonp(string $callback, array $data = [], int $status = 200, array $headers = [])
 * @method static FileResponse file(string $file, array $headers = [])
 * @method static FileResponse download(string $file, string $name = '', array $headers = [], string $disposition = 'attachment')
 * @method static StreamedResponse streamDownload(callable $callback, string $name = '', array $headers = [], string $disposition = 'attachment')
 * @method static RedirectResponse redirect(string $url, int $status = 302, array $headers = [])
 *
 * @see ResponseFactory
 */
class Response extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'response';
    }
}