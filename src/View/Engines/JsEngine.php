<?php declare(strict_types=1);

namespace Imhotep\View\Engines;

class JsEngine extends FileEngine
{
    public function get(string $path): string
    {
        return '<script>'.parent::get($path).'</script>';
    }
}