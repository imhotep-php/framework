<?php

declare(strict_types=1);

namespace Imhotep\Support;

class MimeTypes
{
    public static function add(array $mimeTypes): void
    {
        foreach ($mimeTypes as $mimeType => $extensions) {
            if (! is_string($mimeType)) continue;

            $mimeType = strtolower($mimeType);

            $extensions = (array)$extensions;
            foreach ($extensions as $key => $extension) {
                if (! is_string($extension)) {
                    unset($extensions[$key]);
                }

                $extensions[$key] = strtolower($extension);

                if (isset(self::$EXTENSIONS[$extension])) {
                    self::$EXTENSIONS[$extension][] = $extension;
                }
                else {
                    self::$EXTENSIONS[$extension] = [$extension];
                }
            }

            if (isset(self::$MIME_TYPES[$mimeType])) {
                self::$MIME_TYPES[$mimeType] = array_merge(self::$MIME_TYPES[$mimeType], array_values($extensions));
            }
            else {
                self::$MIME_TYPES[$mimeType] = array_values($extensions);
            }
        }
    }

    public static function getExtensions(string $mimeType): ?array
    {
        if ($mimeType === '') return null;

        return self::$MIME_TYPES[$mimeType] ?? null;
    }

    public static function getExtension(string $mimeType): ?string
    {
        if ($mimeType === '') return null;

        return isset(self::$MIME_TYPES[$mimeType]) ? self::$MIME_TYPES[$mimeType][0] : null;
    }

    public static function getMimeTypes(string $extension): ?array
    {
        if ($extension === '') return null;

        return self::$EXTENSIONS[$extension] ?? null;
    }

    public static function getMimeType(string $extension): ?string
    {
        if ($extension === '') return null;

        return isset(self::$EXTENSIONS[$extension]) ? self::$EXTENSIONS[$extension][0] : null;
    }

    public static function guessMimeType(string $path): ?string
    {
        return mime_content_type($path) ?? null;
    }

    protected static array $MIME_TYPES = [
        'application/acrobat' => ['pdf'],
        'application/bzip2' => ['bz2', 'bz'],
        'application/gzip' => ['gz'],
        'application/ico' => ['ico'],
        'application/json' => ['json', 'map'],
        'application/msexcel' => ['xls', 'xlc', 'xll', 'xlm', 'xlw', 'xla', 'xlt', 'xld'],
        'application/msword' => ['doc'],
        'application/nappdf' => ['pdf'],
        'application/pdf' => ['pdf'],
        'application/zip' => ['zip'],
        'application/x-zip' => ['zip'],
        'application/x-zip-compressed' => ['zip'],
        'application/x-rar-compressed' => ['rar'],
        'application/vnd.rar' => ['rar'],
        'application/x-rar' => ['rar'],
        'image/x-bmp' => ['bmp', 'dib'],
        'image/x-cdr' => ['cdr'],
        'image/x-jpeg2000-image' => ['jp2', 'jpg2'],
        'image/x-tga' => ['tga', 'icb', 'tpic', 'vda', 'vst'],
        'image/bmp' => ['bmp'],
        'image/x-ms-bmp' => ['bmp'],
        'image/png' => ['png'],
        'image/gif' => ['gif'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/pjpeg' => ['jpg', 'jpeg'],
        'image/ico' => ['ico'],
        'image/icon' => ['ico'],
        'image/x-ico' => ['ico'],
        'image/x-icon' => ['ico'],
        'image/webp' => ['webp'],
        'image/svg' => ['svg'],
        'image/svg+xml' => ['svg'],
    ];

    protected static array $EXTENSIONS = [
        'bmp' => ['image/bmp', 'image/x-bmp', 'image/x-ms-bmp'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'jpg' => ['image/jpeg', 'image/pjpeg'],
        'ico' => ['image/ico', 'image/icon', 'image/x-ico', 'image/x-icon'],
        'webp' => ['image/webp'],
        'svg' => ['image/svg', 'image/svg+xml'],
        'json' => ['application/json', 'application/schema+json'],
        'zip' => ['application/zip', 'application/x-zip', 'application/x-zip-compressed'],
        'rar' => ['application/x-rar-compressed', 'application/vnd.rar', 'application/x-rar'],
    ];
}