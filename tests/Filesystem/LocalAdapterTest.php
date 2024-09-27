<?php

namespace Imhotep\Tests\Filesystem;

use Imhotep\Filesystem\Adapters\LocalAdapter;
use Imhotep\Filesystem\Drivers\LocalDriver;
use Imhotep\Support\File;
use PHPUnit\Framework\TestCase;

class LocalAdapterTest extends TestCase
{
    protected string $root = '';

    protected $disk;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->root = __DIR__.'/tmp';

        $this->disk = new LocalAdapter(new LocalDriver(), ['root' => $this->root]);
    }

    protected function setUp(): void
    {
        if (! file_exists($this->root)) {
            mkdir($this->root);
        }
    }

    public function tearDown(): void
    {
        $this->disk->deleteDirectory('/');
    }

    protected function fixPath(string $path = null): string
    {
        return is_null($path) ? $this->root : $this->root .'/'. trim($path, '/');
    }

    public function test_disk_common_methods()
    {
        @touch($this->fixPath('file.txt'));

        $this->assertTrue($this->disk->exists('file.txt'));

        $this->assertTrue($this->disk->delete('file.txt'));

        $this->assertTrue($this->disk->missing('file.txt'));
    }

    public function test_disk_file_visibility()
    {
        @touch($this->fixPath('file.txt'));

        // Set manual public
        chmod($this->fixPath('file.txt'), 0664);

        $this->assertSame('public', $this->disk->visibility('file.txt'));

        $this->assertTrue($this->disk->visibility('file.txt', 'private'));

        $this->assertSame('private', $this->disk->visibility('file.txt'));

        $this->assertTrue($this->disk->visibility('file.txt', 'public'));

        $this->assertSame('public', $this->disk->visibility('file.txt'));
    }

    public function test_disk_dir_visibility()
    {
        @mkdir($this->fixPath('folder'));

        // Set manual public
        @chmod($this->fixPath('folder'), 0775);

        $this->assertSame('public', $this->disk->visibility('folder'));

        $this->assertTrue($this->disk->visibility('folder', 'private'));

        $this->assertSame('private', $this->disk->visibility('folder'));

        $this->assertTrue($this->disk->visibility('folder', 'public'));

        $this->assertSame('public', $this->disk->visibility('folder'));
    }

    public function test_disk_put_method()
    {
        $this->assertSame(12, $this->disk->put('file.txt', 'Hello World!', 'private'));

        $this->assertSame('Hello World!', file_get_contents($this->fixPath('file.txt')));

        $this->assertSame('private', $this->disk->visibility('file.txt'));
    }

    public function test_disk_putFile_method()
    {
        @file_put_contents($this->fixPath('source.txt'), "Example content...");

        $file = new File($this->fixPath('source.txt'));

        $this->disk->ensureDirectoryExists('foo');

        $path = $this->disk->putFile('/foo', $file, 'private');

        $this->assertSame('Example content...', file_get_contents($this->fixPath($path)));

        $this->assertSame('private', $this->disk->visibility($path));
    }

}