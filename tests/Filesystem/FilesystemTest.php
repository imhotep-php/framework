<?php

namespace Imhotep\Tests\Filesystem;

use Imhotep\Contracts\Filesystem\FileNotFoundException;
use Imhotep\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

class FilesystemTest extends TestCase
{
    protected string $root = __DIR__.'/tmp';

    protected Filesystem $files;

    protected function setUp(): void
    {
        if (! is_dir($this->root)) {
            mkdir($this->root);
        }

        $this->files = new Filesystem();
    }

    public function tearDown(): void
    {
        $this->files->deleteDirectory($this->root);
    }

    protected function fixPath($path = null)
    {
        return is_null($path) ? $this->root : $this->root .'/'. trim($path, '/');
    }

    public function test_exists_missing_methods()
    {
        $path = $this->fixPath('file.txt');

        touch($path);

        $this->assertTrue($this->files->exists($path));
        $this->assertFalse($this->files->missing($path));

        unlink($path);

        $this->assertFalse($this->files->exists($path));
        $this->assertTrue($this->files->missing($path));
    }

    public function test_files_method()
    {
        touch($this->fixPath("file1.txt"));
        touch($this->fixPath("file2.txt"));

        $results = $this->files->files($this->root);

        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertInstanceOf(SplFileInfo::class, $result);
        }

        $filenames = array_map(fn($f) => $f->getFilename(), $results);
        sort($filenames);
        $this->assertEquals(['file1.txt', 'file2.txt'], $filenames);
    }

    public function test_allFiles_method()
    {
        touch($this->fixPath('/file1.txt'));
        touch($this->fixPath('/file2.txt'));
        mkdir($this->fixPath('/foo'));
        touch($this->fixPath('/foo/file3.txt'));
        mkdir($this->fixPath('/foo/fooInn'));
        touch($this->fixPath('/foo/fooInn/file4.txt'));
        mkdir($this->fixPath('/bar'));
        touch($this->fixPath('/bar/file5.txt'));

        $results = $this->files->allFiles($this->root);

        foreach ($results as $key => $item) {
            $results[$key] = str_replace($this->root, "", $item->getRealPath());
        }



        $expected = [
            '/bar/file5.txt',
            '/foo/fooInn/file4.txt',
            '/foo/file3.txt',
            '/file1.txt',
            '/file2.txt'
        ];

        sort($expected);
        sort($results);

        $this->assertEquals($expected, $results);
    }

    public function test_directories_method()
    {
        $dirs = ['/foo2', '/bar2', '/foo1', '/bar11', '/bar1'];

        foreach ($dirs as $dir) {
            @mkdir($this->fixPath($dir));
        }

        $results = $this->files->directories($this->root);

        $expected = array_map([$this, 'fixPath'], $dirs);

        sort($expected);
        sort($results);

        $this->assertSame($expected, $results);
    }

    public function test_allDirectories_method()
    {
        $dirs = ['/foo', '/foo/fooInn', '/bar', '/bar/barInn'];

        foreach ($dirs as $dir) {
            @mkdir($this->fixPath($dir));
        }

        $results = $this->files->allDirectories($this->root);

        $expected = array_map([$this, 'fixPath'], $dirs);

        sort($expected);
        sort($results);

        $this->assertSame($expected, $results);
    }

    public function test_isFile_method()
    {
        $path = $this->fixPath('file.txt');

        $this->assertFalse($this->files->isFile($path));

        @touch($path);

        $this->assertTrue($this->files->isFile($path));
    }

    public function test_json_method()
    {
        $path = $this->fixPath('data.json');

        $data = ['name' => 'John', 'age' => 30];
        file_put_contents($path, json_encode($data));

        $this->assertEquals($data, $this->files->json($path));
        $this->assertEquals($data, $this->files->json($path, 512, JSON_OBJECT_AS_ARRAY));


        //$this->expectException(FileNotFoundException::class);
        //$this->files->json($this->fixPath('nonexistent.json'));
    }

    public function test_require_method()
    {
        $path = $this->fixPath('config.php');

        $content = "<?php return ['db' => 'mysql', 'debug' => true];";
        file_put_contents($path, $content);

        $result = $this->files->require($path);
        $this->assertEquals(['db' => 'mysql', 'debug' => true], $result);

        // Test with data
        $content = "<?php return ['name' => \$name ?? 'default'];";
        file_put_contents($path, $content);

        $result = $this->files->require($path, ['name' => 'John']);
        $this->assertEquals(['name' => 'John'], $result);

        $this->expectException(FileNotFoundException::class);
        $this->files->require($this->fixPath('nonexistent.php'));
    }

    public function test_requireOnce_method()
    {
        $path = $this->fixPath('config_once.php');

        $content = "<?php return ['db' => 'mysql', 'debug' => true];";
        file_put_contents($path, $content);

        // Первый require_once должен загрузить файл
        $result1 = $this->files->requireOnce($path);
        $this->assertEquals(['db' => 'mysql', 'debug' => true], $result1);

        // Второй require_once должен вернуть true (файл уже загружен)
        $result2 = $this->files->requireOnce($path);
        $this->assertTrue($result2);

        $this->expectException(FileNotFoundException::class);
        $this->files->requireOnce($this->fixPath('nonexistent.php'));
    }

    public function test_getWithLock_method()
    {
        $path = $this->fixPath('file.txt');
        $content = "Hello World!";
        file_put_contents($path, $content);
        $this->assertEquals($content, $this->files->getWithLock($path));


        //$this->expectException(FileNotFoundException::class);
        //$this->files->getWithLock($this->fixPath('nonexistent.txt'));
    }

    public function test_get_method()
    {
        $path = $this->fixPath('file1.txt');

        file_put_contents($path, "Hello World!");

        $this->assertEquals('Hello World!', $this->files->get($path));

        @unlink($path);

        //$this->expectException(FileNotFoundException::class);
        //$this->files->get($path);
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

        unlink($path);

        // @TODO: not work...
        //$this->expectException(FileNotFoundException::class);
        //$this->files->lines($this->fixPath('nonexistent.txt'));
    }

    public function test_put_method()
    {
        $path = $this->fixPath('file.txt');

        $this->files->put($path, "Hello World!");

        $this->assertSame("Hello World!", $this->files->get($path));
    }

    public function test_append_method()
    {
        $path = $this->fixPath('file.txt');

        $this->assertIsInt($this->files->append($path, "Line 1\n"));
        $this->assertEquals("Line 1\n", $this->files->get($path));

        $this->assertIsInt($this->files->append($path, "Line 2"));
        $this->assertEquals("Line 1\nLine 2", $this->files->get($path));

        $this->assertIsInt($this->files->append($path, "Line 3", PHP_EOL, true));
        $this->assertEquals("Line 1\nLine 2\nLine 3", $this->files->get($path));

        $result = $this->files->append($path, "\nLine 4", ' _sep_ ', true);
        $this->assertIsInt($result);
        $this->assertStringContainsString("Line 1\nLine 2\nLine 3 _sep_ \nLine 4", $this->files->get($path));

        // Добавление в пустой файл с разделителем (разделитель не должен добавиться)
        $emptyPath = $this->fixPath('empty.txt');
        $this->files->put($emptyPath, '');
        $this->assertIsInt($this->files->append($emptyPath, "Content", PHP_EOL));
        $this->assertEquals("Content", $this->files->get($emptyPath));
    }

    public function test_prepend_method()
    {
        $path = $this->fixPath('file.txt');

        // Создание нового файла с контентом (без разделителя)
        $this->assertIsInt($this->files->prepend($path, 'First line'));
        $this->assertEquals('First line', $this->files->get($path));

        // Добавление в начало существующего файла (без разделителя)
        $this->assertIsInt($this->files->prepend($path, 'Second line'));
        $this->assertEquals('Second lineFirst line', $this->files->get($path));

        // Добавление в начало с разделителем
        $this->assertIsInt($this->files->prepend($path, 'Third line', PHP_EOL));
        $this->assertEquals("Third line\nSecond lineFirst line", $this->files->get($path));

        // Добавление в пустой файл с разделителем (разделитель не должен добавиться)
        $emptyPath = $this->fixPath('empty.txt');
        $this->files->put($emptyPath, '');
        $this->assertIsInt($this->files->prepend($emptyPath, 'Content', PHP_EOL));
        $this->assertEquals('Content', $this->files->get($emptyPath));
    }

    public function test_replace_method()
    {
        $path = $this->fixPath('file.txt');
        file_put_contents($path, "Old content");

        $this->assertTrue($this->files->replace($path, "Updated content"));
        $this->assertEquals("Updated content", $this->files->get($path));
    }

    public function test_read_stream()
    {
        $path = $this->fixPath('read_stream_test.txt');
        $content = "Test content for stream reading";
        $this->files->put($path, $content);

        // Read OK
        $stream = $this->files->readStream($path);
        $this->assertIsResource($stream);
        $this->assertEquals($content, stream_get_contents($stream));
        fclose($stream);
    }

    public function test_write_stream()
    {
        $path = $this->fixPath('write_test.txt');
        $content = "Content for stream writing";

        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $content);
        rewind($resource);

        $this->assertTrue($this->files->writeStream($path, $resource));
        $this->assertEquals($content, $this->files->get($path));

        fclose($resource);
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

        //$this->expectException(FileNotFoundException::class);
        //$this->files->get($path_to);
        $this->assertFalse($this->files->exists($path_to));

        $this->assertTrue($this->files->move($path_from, $path_to));

        $this->assertFalse($this->files->exists($path_from));

        //$this->expectException(FileNotFoundException::class);
        //$this->files->get($path_from);

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

        $this->files->setPermissions($path, 0755);

        $expectedPermissions = DIRECTORY_SEPARATOR === '\\' ? '0666' : '0755';

        $this->assertEquals($expectedPermissions, $this->files->permissions($path));
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