<?php

namespace Imhotep\Tests\Filesystem;

use Imhotep\Config\Repository;
use Imhotep\Contracts\Filesystem\IFilesystem;
use Imhotep\Filesystem\Adapters\LocalAdapter;
use Imhotep\Filesystem\Drivers\LocalDriver;
use Imhotep\Support\File;

class LocalDiskTest extends AbstractDiskTestCase
{
    protected string $root = __DIR__.'/tmp_tests';

    protected function createTestEnvironment(): void
    {
        if (! file_exists($this->root)) {
            mkdir($this->root, 0755, true);
        }
    }

    protected function cleanTestEnvironment(): void
    {
        rmdir($this->root);
    }

    protected function createDisk(): IFilesystem
    {
        return new LocalAdapter(new LocalDriver(true), new Repository([
            'root' => $this->root
        ]));
    }

    protected function resolvePath($path = null): string
    {
        return is_null($path) ? $this->root : $this->root .'/'. trim($path, '/');
    }


    /*
    public function test_disk_common_methods()
    {
        @touch($this->resolvePath('file.txt'));

        $this->assertTrue($this->disk->exists('file.txt'));

        $this->assertTrue($this->disk->delete('file.txt'));

        $this->assertTrue($this->disk->missing('file.txt'));
    }

    public function test_disk_file_visibility()
    {
        @touch($this->resolvePath('file.txt'));

        // Set manual public
        chmod($this->resolvePath('file.txt'), 0664);

        $this->assertSame('public', $this->disk->visibility('file.txt'));
        $this->assertTrue($this->disk->setVisibility('file.txt', 'private'));

        $this->assertSame('private', $this->disk->visibility('file.txt'));
        $this->assertTrue($this->disk->setVisibility('file.txt', 'public'));

        $this->assertSame('public', $this->disk->visibility('file.txt'));
    }

    public function test_disk_dir_visibility()
    {
        @mkdir($this->resolvePath('folder'));

        // Set manual public
        @chmod($this->resolvePath('folder'), 0775);
        $this->assertSame('public', $this->disk->visibility('folder'));

        $this->assertTrue($this->disk->setVisibility('folder', 'private'));
        $this->assertSame('private', $this->disk->visibility('folder'));

        $this->assertTrue($this->disk->setVisibility('folder', 'public'));
        $this->assertSame('public', $this->disk->visibility('folder'));
    }

    public function test_disk_put_method()
    {
        $this->assertSame(12, $this->disk->put('file.txt', 'Hello World!', 'private'));
        $this->assertSame('Hello World!', file_get_contents($this->resolvePath('file.txt')));
        $this->assertSame('private', $this->disk->visibility('file.txt'));
    }

    public function test_disk_putFile_method()
    {
        file_put_contents($this->resolvePath('source.txt'), "Example content...");

        $file = new File($this->resolvePath('source.txt'));

        $this->disk->ensureDirectoryExists('foo');

        $this->assertNotFalse($this->disk->putFile($path = '/foo', $file, 'private'));

        $this->assertSame('Example content...', file_get_contents($this->resolvePath($path)));
        $this->assertSame('private', $this->disk->visibility($path));

        $this->disk->delete('source.txt');
    }
    */
}