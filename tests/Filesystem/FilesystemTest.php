<?php

namespace Imhotep\Tests\Filesystem;

use Imhotep\Filesystem\Drivers\LocalDriver;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class FilesystemTest extends TestCase
{
    protected string $root = '';

    protected LocalDriver $files;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->root = __DIR__.'/tmp';

        $this->files = new LocalDriver(['throw' => false]);

        $this->files->makeDirectory($this->root);
    }

    public function tearDown(): void
    {
        $this->files->deleteDirectory($this->root);
    }

    protected function fixPath(string $path = null)
    {
        return is_null($path) ? $this->root : $this->root .'/'. trim($path, '/');
    }

    public function test_exists_missing_methods()
    {
        $path = $this->fixPath('file.txt');

        @touch($path);

        $this->assertTrue($this->files->exists($path));
        $this->assertFalse($this->files->missing($path));

        @unlink($path);

        $this->assertFalse($this->files->exists($path));
        $this->assertTrue($this->files->missing($path));
    }

    public function test_files_method()
    {
        @touch($this->fixPath("file1.txt"));
        @touch($this->fixPath("file2.txt"));

        $results = $this->files->files($this->root);

        $this->assertInstanceOf(SplFileInfo::class, $results[0]);
        $this->assertInstanceOf(SplFileInfo::class, $results[1]);
        $this->assertArrayNotHasKey(2, $results);
    }

    public function test_allFiles_method()
    {
        @touch($this->fixPath('/file1.txt'));
        @touch($this->fixPath('/file2.txt'));
        @mkdir($this->fixPath('/foo'));
        @touch($this->fixPath('/foo/file3.txt'));
        @mkdir($this->fixPath('/foo/fooInn'));
        @touch($this->fixPath('/foo/fooInn/file4.txt'));
        @mkdir($this->fixPath('/bar'));
        @touch($this->fixPath('/bar/file5.txt'));

        $results = $this->files->allFiles($this->root);

        foreach ($results as $key => $item) {
            $results[$key] = str_replace($this->root, "", $item->getRealPath());
        }

        $this->assertSame('/bar/file5.txt', $results[0]);
        $this->assertSame('/foo/fooInn/file4.txt', $results[1]);
        $this->assertSame('/foo/file3.txt', $results[2]);
        $this->assertSame('/file1.txt', $results[3]);
        $this->assertSame('/file2.txt', $results[4]);
    }

    public function test_directories_method()
    {
        @mkdir($this->fixPath('/foo2'));
        @mkdir($this->fixPath('/bar2'));
        @mkdir($this->fixPath('/foo1'));
        @mkdir($this->fixPath('/bar11'));
        @mkdir($this->fixPath('/bar1'));

        $results = $this->files->directories($this->root);


        $this->assertSame($this->fixPath('/bar1'), $results[0]);
        $this->assertSame($this->fixPath('/bar2'), $results[1]);
        $this->assertSame($this->fixPath('/bar11'), $results[2]);
        $this->assertSame($this->fixPath('/foo1'), $results[3]);
        $this->assertSame($this->fixPath('/foo2'), $results[4]);
    }

    public function test_allDirectories_method()
    {
        @mkdir($this->fixPath('/foo'));
        @mkdir($this->fixPath('/foo/fooInn'));
        @mkdir($this->fixPath('/bar'));
        @mkdir($this->fixPath('/bar/barInn'));

        $results = $this->files->allDirectories($this->root);

        $this->assertSame($this->fixPath('/bar'), $results[0]);
        $this->assertSame($this->fixPath('/bar/barInn'), $results[1]);
        $this->assertSame($this->fixPath('/foo'), $results[2]);
        $this->assertSame($this->fixPath('/foo/fooInn'), $results[3]);

    }

    public function test_isFile_method()
    {
        $path = $this->fixPath('file.txt');

        $this->assertFalse($this->files->isFile($path));

        @touch($path);

        $this->assertTrue($this->files->isFile($path));
    }

    public function test_get_method()
    {
        $path = $this->fixPath('file1.txt');

        file_put_contents($path, "Hello World!");

        $this->assertEquals('Hello World!', $this->files->get($path));

        @unlink($path);

        $this->assertFalse($this->files->get($path));
    }

    public function test_lines_method()
    {
        $path = $this->fixPath('file1.txt');

        file_put_contents($path, "Line 1\n", FILE_APPEND);
        file_put_contents($path, "Line 2\n", FILE_APPEND);
        file_put_contents($path, "Line 3\n", FILE_APPEND);
        file_put_contents($path, "\n", FILE_APPEND);
        file_put_contents($path, "Line 4", FILE_APPEND);

        $this->assertSame(
            ['Line 1', 'Line 2', 'Line 3', '', 'Line 4'],
            iterator_to_array($this->files->lines($path))
        );

        // Skip empty lines
        $this->assertSame(
            ['Line 1', 'Line 2', 'Line 3', 'Line 4'],
            iterator_to_array($this->files->lines($path, true))
        );
    }

    public function test_put_method()
    {
        $path = $this->fixPath('file.txt');

        $this->files->put($path, "Hello World!");

        $this->assertSame("Hello World!", $this->files->get($path));
    }

    public function test_copy_method()
    {
        $path_from = $this->fixPath('file1.txt');
        $path_to = $this->fixPath('file2.txt');

        @file_put_contents($path_from, "foo");
        @file_put_contents($path_to, "bar");

        $this->assertEquals('foo', $this->files->get($path_from));
        $this->assertEquals('bar', $this->files->get($path_to));

        $this->assertTrue($this->files->copy($path_from, $path_to));

        $this->assertEquals('foo', $this->files->get($path_from));
        $this->assertEquals('foo', $this->files->get($path_to));
    }

    public function test_move_method()
    {
        $path_from = $this->fixPath('file1.txt');
        $path_to = $this->fixPath('file2.txt');

        @file_put_contents($path_from, "foo");

        $this->assertEquals('foo', $this->files->get($path_from));
        $this->assertFalse($this->files->get($path_to));

        $this->assertTrue($this->files->move($path_from, $path_to));

        $this->assertFalse($this->files->get($path_from));
        $this->assertEquals('foo', $this->files->get($path_to));
    }

    public function test_size_method()
    {
        $path = $this->fixPath('file.txt');

        $size = file_put_contents($path, "Hello World!");
        $this->assertEquals($size, $this->files->size($path));
    }

    public function test_hash_method()
    {
        $path = $this->fixPath('file.txt');

        file_put_contents($path, 'foo');

        $this->assertSame('0beec7b5ea3f0fdbc95d0dd47f3c5bc275da8a33', $this->files->hash($path, 'sha1'));
        $this->assertSame('76d3bc41c9f588f7fcd0d5bf4718f8f84b1c41b20882703100b9eb9413807c01', $this->files->hash($path, 'sha3-256'));
    }

    public function test_hasSameHash_method()
    {
        $firstPath = $this->fixPath('file1.txt');
        $secondPath = $this->fixPath('file2.txt');
        $otherPath = $this->fixPath('file3.txt');
        $nonExistsPath = $this->fixPath('file4.txt');

        file_put_contents($firstPath, 'contents');
        file_put_contents($secondPath, 'contents');
        file_put_contents($otherPath, 'invalid');

        $this->assertTrue($this->files->hasSameHash($firstPath, $secondPath));
        $this->assertFalse($this->files->hasSameHash($firstPath, $otherPath));
        $this->assertFalse($this->files->hasSameHash($nonExistsPath, $firstPath));
        $this->assertFalse($this->files->hasSameHash($firstPath, $nonExistsPath));
    }

    public function test_type_method()
    {
        $filePath = $this->fixPath('file.txt');
        $dirPath = $this->fixPath('dir');

        @touch($filePath);
        @mkdir($dirPath);

        $this->assertSame('file', $this->files->type($filePath));
        $this->assertSame('dir', $this->files->type($dirPath));
    }

    public function test_mimeType_methods()
    {
        $path = $this->fixPath('file.txt');

        @file_put_contents($path, 'foo');

        $this->assertSame('text/plain', $this->files->mimeType($path));
    }

    public function test_path_info_methods()
    {
        $path = '/foo/bar/baz/super.txt';

        $this->assertSame('super', $this->files->name($path));
        $this->assertSame('super.txt', $this->files->basename($path));
        $this->assertSame('/foo/bar/baz', $this->files->dirname($path));
        $this->assertSame('txt', $this->files->extension($path));
    }

    public function test_chmod_method()
    {
        $path = $this->fixPath('file.txt');

        @touch($path);

        $this->files->chmod($path, 0755);

        $expectedPermissions = DIRECTORY_SEPARATOR === '\\' ? '0666' : '0755';

        $this->assertEquals($expectedPermissions, $this->files->chmod($path));
    }

    public function test_delete_method()
    {
        $file1 = $this->fixPath('file1.txt');
        $file2 = $this->fixPath('file2.txt');
        $file3 = $this->fixPath('file3.txt');

        @touch($file1);
        @touch($file2);
        @touch($file3);

        $this->assertTrue(file_exists($file1));
        $this->files->delete($file1);
        $this->assertFalse(file_exists($file1));

        $this->assertTrue(file_exists($file2));
        $this->assertTrue(file_exists($file3));
        $this->files->delete([$file2, $file3]);
        $this->assertFalse(file_exists($file2));
        $this->assertFalse(file_exists($file3));
    }

    public function test_isDirectory_method()
    {
        $path = $this->fixPath('dir');

        @mkdir($path);

        $this->assertTrue($this->files->isDirectory($path));
    }

    public function test_makeDirectory_method()
    {
        $path = $this->fixPath('dir');

        $this->assertTrue($this->files->makeDirectory($path));
        $this->assertTrue(is_dir($path));
        $this->assertTrue(file_exists($path));
    }

    public function test_moveDirectory_method()
    {
        @mkdir($this->fixPath('dir1'), 0777, true);
        @touch($this->fixPath('dir1/foo.txt'));
        @touch($this->fixPath('dir1/bar.txt'));

        $this->assertTrue($this->files->moveDirectory($this->fixPath('dir1'), $this->fixPath('dir2')));

        $this->assertTrue(file_exists($this->fixPath('dir2')));
        $this->assertTrue(file_exists($this->fixPath('dir2/foo.txt')));
        $this->assertTrue(file_exists($this->fixPath('dir2/bar.txt')));
    }

    public function test_moveDirectory_method_overwrite()
    {
        @mkdir($this->fixPath('dir1'), 0777, true);
        @file_put_contents($this->fixPath('dir1/foo.txt'), 'foo');
        @file_put_contents($this->fixPath('dir1/bar.txt'), 'bar');

        @mkdir($this->fixPath('dir2'), 0777, true);
        @file_put_contents($this->fixPath('dir2/foo.txt'), 'foo2');
        @file_put_contents($this->fixPath('dir2/bar2.txt'), 'bar2');

        // Without overwrite
        $this->assertFalse($this->files->moveDirectory($this->fixPath('dir1'), $this->fixPath('dir2')));
        $this->assertSame('foo2', $this->files->get($this->fixPath('dir2/foo.txt')));

        // Overwrite
        $this->assertTrue($this->files->moveDirectory($this->fixPath('dir1'), $this->fixPath('dir2'), true));
        $this->assertSame('foo', $this->files->get($this->fixPath('dir2/foo.txt')));
        $this->assertSame('bar', $this->files->get($this->fixPath('dir2/bar.txt')));
        $this->assertTrue($this->files->missing($this->fixPath('dir2/bar2.txt')));
    }

    public function test_copyDirectory_method()
    {
        @mkdir($this->fixPath('dir1'), 0777, true);
        @file_put_contents($this->fixPath('dir1/foo.txt'), 'foo_0');
        @file_put_contents($this->fixPath('dir1/bar.txt'), 'bar_0');

        @mkdir($this->fixPath('dir2'), 0777, true);
        @file_put_contents($this->fixPath('dir2/foo.txt'), 'foo_1');
        @file_put_contents($this->fixPath('dir2/bar.txt'), 'bar_1');
        @file_put_contents($this->fixPath('dir2/foo2.txt'), 'foo_2');
        @file_put_contents($this->fixPath('dir2/bar2.txt'), 'bar_2');

        $this->assertSame('foo_1', $this->files->get($this->fixPath('dir2/foo.txt')));
        $this->assertSame('bar_1', $this->files->get($this->fixPath('dir2/bar.txt')));

        $this->assertTrue( $this->files->copyDirectory($this->fixPath('dir1'), $this->fixPath('dir2')) );

        $this->assertSame('foo_0', $this->files->get($this->fixPath('dir2/foo.txt')));
        $this->assertSame('bar_0', $this->files->get($this->fixPath('dir2/bar.txt')));
        $this->assertSame('foo_2', $this->files->get($this->fixPath('dir2/foo2.txt')));
        $this->assertSame('bar_2', $this->files->get($this->fixPath('dir2/bar2.txt')));
    }

    public function test_cleanDirectory_method()
    {
        @mkdir($this->fixPath('dir'), 0777, true);
        @touch($this->fixPath('dir/foo.txt'));
        @touch($this->fixPath('dir/bar.txt'));

        $this->assertTrue($this->files->cleanDirectory($this->fixPath('dir')));

        $this->assertTrue($this->files->missing($this->fixPath('dir/foo.txt')));
        $this->assertTrue($this->files->missing($this->fixPath('dir/bar.txt')));
    }
}