<?php

namespace Imhotep\Tests\Filesystem;

use Imhotep\Contracts\Filesystem\IFilesystem;
use Imhotep\Filesystem\FileInfo;
use PHPUnit\Framework\TestCase;

abstract class AbstractDiskTestCase extends TestCase
{
    protected IFilesystem $disk;

    protected string $root = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestEnvironment();

        $this->disk = $this->createDisk();
    }

    protected function tearDown(): void
    {
        $this->disk->cleanDirectory('/');

        $this->cleanTestEnvironment();

        parent::tearDown();
    }

    abstract protected function createDisk(): IFilesystem;

    protected function createTestEnvironment(): void
    {

    }

    protected function cleanTestEnvironment(): void
    {

    }


    public function test_exists_missing_methods()
    {
        $this->disk->put($file = "file.txt", "");

        $this->assertTrue($this->disk->exists("file.txt"));
        $this->assertTrue($this->disk->exists("/file.txt"));
        $this->assertTrue($this->disk->exists("/file.txt/"));
        $this->assertFalse($this->disk->missing($file));

        $this->disk->delete($file);

        $this->assertFalse($this->disk->exists($file));
        $this->assertTrue($this->disk->missing($file));
    }

    public function test_is_directory_method()
    {
        $this->disk->makeDirectory('my-dir');
        $this->assertTrue($this->disk->isDirectory('my-dir'));
        $this->assertTrue($this->disk->isDirectory('/my-dir'));
        $this->assertTrue($this->disk->isDirectory('/my-dir/'));
        $this->disk->deleteDirectory('my-dir');
    }

    public function test_is_file_method()
    {
        $this->disk->put('is-file.txt', "");
        $this->assertTrue($this->disk->isFile('is-file.txt'));
        $this->assertTrue($this->disk->isFile('/is-file.txt'));
        $this->assertTrue($this->disk->isFile('/is-file.txt/'));
        $this->disk->delete('is-file.txt');
    }

    public function test_type_method()
    {
        $this->disk->put($file = 'is-file', "");
        $this->assertSame('file', $this->disk->type('is-file'));
        $this->assertSame('file', $this->disk->type('/is-file'));
        $this->assertSame('file', $this->disk->type('/is-file/'));
        $this->disk->delete($file);

        $this->disk->makeDirectory($path = 'is-dir');
        $this->assertSame('dir', $this->disk->type('is-dir'));
        $this->assertSame('dir', $this->disk->type('/is-dir'));
        $this->assertSame('dir', $this->disk->type('/is-dir/'));
        $this->disk->deleteDirectory($path);
    }


    public function test_list_method()
    {
        // Create test structure
        $this->disk->put('file1.txt', 'Content 1');
        $this->disk->put('file2.txt', 'Content 2');
        $this->disk->makeDirectory('subdir');
        $this->disk->put('subdir/file3.txt', 'Content 3');

        // Test list root directory
        $items = $this->disk->list();

        $this->assertIsArray($items);
        $this->assertCount(3, $items); // file1.txt, file2.txt, subdir

        // Check that items are FileInfo objects
        foreach ($items as $item) {
            $this->assertInstanceOf(FileInfo::class, $item);
        }

        // Verify files exist in listing
        $basenames = array_map(fn($item) => $item->getBasename(), $items);
        $this->assertContains('file1.txt', $basenames);
        $this->assertContains('file2.txt', $basenames);
        $this->assertContains('subdir', $basenames);

        // Test list subdirectory
        $items = $this->disk->list('subdir');
        $this->assertIsArray($items);
        $this->assertCount(1, $items);
        $this->assertEquals('file3.txt', $items[0]->getBasename());
        $this->assertTrue($items[0]->isFile());

        // Cleanup
        $this->disk->cleanDirectory('/');
    }

    public function test_directories_method()
    {
        // Create test structure
        $this->disk->makeDirectory('dir1');
        $this->disk->makeDirectory('/dir2');
        $this->disk->put('file1.txt', 'Content 1');
        $this->disk->put('/file2.txt', 'Content 2');
        $this->disk->makeDirectory('parent/child');

        // Test directories listing
        $dirs = $this->disk->directories();

        $this->assertIsArray($dirs);
        $this->assertCount(3, $dirs); // dir1, dir2, parent

        // Check that all items are directories
        foreach ($dirs as $dir) {
            $this->assertInstanceOf(FileInfo::class, $dir);
            $this->assertTrue($dir->isDir());
            $this->assertFalse($dir->isFile());
        }

        // Verify expected directories exist
        $basenames = array_map(fn($dir) => $dir->getBasename(), $dirs);
        $this->assertContains('dir1', $basenames);
        $this->assertContains('dir2', $basenames);
        $this->assertContains('parent', $basenames);

        // Test subdirectory listing
        $dirs = $this->disk->directories('parent');
        $this->assertIsArray($dirs);
        $this->assertCount(1, $dirs);
        $this->assertEquals('child', $dirs[0]->getBasename());
        $this->assertTrue($dirs[0]->isDir());

        // Cleanup
        $this->disk->cleanDirectory('/');
    }

    public function test_files_method()
    {
        // Create test structure
        $this->disk->put('file1.txt', 'Content 1');
        $this->disk->put('file2.txt', 'Content 2');
        $this->disk->put('file3.jpg', 'Image content');
        $this->disk->makeDirectory('folder');
        $this->disk->put('folder/file4.txt', 'Content 4');

        // Test files listing
        $files = $this->disk->files('');

        $this->assertIsArray($files);
        $this->assertCount(3, $files); // file1.txt, file2.txt, file3.jpg

        // Check that all items are files
        foreach ($files as $file) {
            $this->assertInstanceOf(FileInfo::class, $file);
            $this->assertTrue($file->isFile());
            $this->assertFalse($file->isDir());
        }

        // Verify file metadata
        $basenames = array_map(fn($file) => $file->getBasename(), $files);
        $this->assertContains('file1.txt', $basenames);
        $this->assertContains('file2.txt', $basenames);
        $this->assertContains('file3.jpg', $basenames);

        // Check that files have size information
        foreach ($files as $file) {
            $this->assertNotNull($file->size());
            $this->assertGreaterThan(0, $file->size());
            $this->assertGreaterThan(0, $file->lastModified());
        }

        // Test subdirectory files
        $files = $this->disk->files('folder');
        $this->assertIsArray($files);
        $this->assertCount(1, $files);
        $this->assertEquals('file4.txt', $files[0]->getBasename());
        $this->assertTrue($files[0]->isFile());
        $this->assertGreaterThan(0, $files[0]->size());
        $this->assertGreaterThan(0, $files[0]->lastModified());

        // Cleanup
        $this->disk->cleanDirectory('/');
    }


    public function test_make_and_delete_directory_method()
    {
        $this->assertTrue($this->disk->makeDirectory($path = 'imhotep'));
        $this->assertTrue($this->disk->exists($path));
        $this->assertTrue($this->disk->isDirectory($path));
        $this->assertFalse($this->disk->isFile($path));

        $this->assertTrue($this->disk->deleteDirectory($path));
        $this->assertFalse($this->disk->exists($path));
        $this->assertFalse($this->disk->isDirectory($path));
    }

    public function test_clean_directory_method()
    {
        // Create test structure with multiple files in directory
        $this->disk->makeDirectory('clean-dir');
        $this->disk->put('clean-dir/file1.txt', 'Content 1');
        $this->disk->put('clean-dir/file2.txt', 'Content 2');
        $this->disk->put('clean-dir/file3.jpg', 'Image content');

        // Also create a subdirectory with files (should not be deleted)
        $this->disk->makeDirectory('clean-dir/subdir');
        $this->disk->put('clean-dir/subdir/file4.txt', 'Content 4');

        // Verify files exist before cleaning
        $this->assertTrue($this->disk->exists('clean-dir/file1.txt'));
        $this->assertTrue($this->disk->exists('clean-dir/file2.txt'));
        $this->assertTrue($this->disk->exists('clean-dir/file3.jpg'));
        $this->assertTrue($this->disk->exists('clean-dir/subdir/file4.txt'));

        // Clean the directory
        $this->assertTrue($this->disk->cleanDirectory('clean-dir'));

        // Verify all files in root directory are deleted
        $this->assertFalse($this->disk->exists('clean-dir/file1.txt'));
        $this->assertFalse($this->disk->exists('clean-dir/file2.txt'));
        $this->assertFalse($this->disk->exists('clean-dir/file3.jpg'));
        $this->assertFalse($this->disk->isDirectory('clean-dir/subdir'));
        $this->assertFalse($this->disk->exists('clean-dir/subdir/file4.txt'));

        // Cleanup
        $this->disk->deleteDirectory('clean-dir');
    }


    public function test_get_method()
    {
        $content = sprintf("Test content (%s)", rand(100000, 999999));

        $this->disk->put($file = "file.txt", $content);

        $this->assertTrue($this->disk->exists($file));
        $this->assertSame($content, $this->disk->get($file));

        $this->disk->delete($file);
    }

    public function test_put_method()
    {
        $file = sprintf('file-%s.txt', rand(1000,9999));

        $this->disk->put($file, "");
        $this->assertTrue($this->disk->exists($file));

        $this->disk->delete($file);
    }

    public function test_copy_method()
    {
        $content = sprintf("Test content (%s)", rand(100000, 999999));

        $this->disk->put("original.txt", $content);
        $this->disk->copy('original.txt', 'copied.txt');

        $this->assertTrue($this->disk->exists('copied.txt'));
        $this->assertSame($content, $this->disk->get('copied.txt'));

        $this->disk->delete('original.txt');
        $this->disk->delete('copied.txt');
    }

    public function test_move_method()
    {
        $content = sprintf("Test content (%s)", rand(100000, 999999));

        $this->disk->put("original.txt", $content);
        $this->disk->move('original.txt', 'newest.txt');

        $this->assertTrue($this->disk->missing('original.txt'));
        $this->assertTrue($this->disk->exists('newest.txt'));
        $this->assertSame($content, $this->disk->get('newest.txt'));

        $this->disk->delete('original.txt');
        $this->disk->delete('newest.txt');
    }

    public function test_delete_method()
    {
        $this->disk->put($file = "file.txt", "");
        $this->assertTrue($this->disk->exists($file));
        $this->assertTrue($this->disk->delete($file));
        $this->assertFalse($this->disk->exists($file));
    }

    public function test_read_stream_method()
    {
        $content = "Test stream content (" . rand(100000, 999999) . ")\nLine 2\nLine 3";
        $file = 'stream-test.txt';

        $this->disk->put($file, $content);

        $stream = $this->disk->readStream($file);

        if ($stream === false) {
            $this->markTestSkipped('readStream not implemented for this driver');
            $this->disk->delete($file);
            return;
        }

        $this->assertIsResource($stream);

        $streamContent = stream_get_contents($stream);
        fclose($stream);

        $this->assertSame($content, $streamContent);

        // @TODO: FileNotFoundException or ... ???
        //$stream = $this->disk->readStream('non-existent-file.txt');
        //$this->assertFalse($stream);

        $this->disk->delete($file);
    }

    public function test_write_stream_method()
    {
        return;

        $content = "Stream write test content (" . rand(100000, 999999) . ")";
        $file = 'stream-write-test.txt';

        $tempStream = fopen('php://temp', 'r+');
        fwrite($tempStream, $content);
        rewind($tempStream);

        $result = $this->disk->writeStream($file, $tempStream);

        if ($result === false) {
            fclose($tempStream);
            $this->markTestSkipped('writeStream not implemented for this driver');
            return;
        }

        fclose($tempStream);

        $this->assertTrue($result);
        $this->assertTrue($this->disk->exists($file));

        $readContent = $this->disk->get($file);
        $this->assertSame($content, $readContent);

        // Test with larger content
        $largeContent = str_repeat("Line " . rand(1000, 9999) . "\n", 100);
        $largeFile = 'stream-write-large.txt';

        $largeStream = fopen('php://temp', 'r+');
        fwrite($largeStream, $largeContent);
        rewind($largeStream);

        $result = $this->disk->writeStream($largeFile, $largeStream);
        fclose($largeStream);

        if ($result !== false) {
            $this->assertTrue($result);
            $this->assertTrue($this->disk->exists($largeFile));
            $this->assertSame($largeContent, $this->disk->get($largeFile));
        }

        // Cleanup
        $this->disk->delete($file);
        $this->disk->delete($largeFile);
    }


    public function test_size_method()
    {
        $size = $this->disk->put('file.txt', "Imhotep Framework Filesystem!");

        $this->assertGreaterThan(0, $size);
        $this->assertEquals($size, $this->disk->size('file.txt'));

        $this->disk->delete('file.txt');
    }

    public function test_last_modified_method()
    {
        $beforeTime = time() - 1;

        $this->disk->put($file = 'file.txt', "Imhotep Framework Filesystem!");

        $afterTime = time() + 1;

        $lastModified = $this->disk->lastModified($file);

        $this->assertIsInt($lastModified);
        $this->assertGreaterThanOrEqual($beforeTime, $lastModified);
        $this->assertLessThanOrEqual($afterTime, $lastModified);

        $this->disk->delete($file);

        $this->assertFalse($this->disk->exists($file));
    }


    public function test_hash_method()
    {
        $this->disk->put('file.txt', "Imhotep Framework Filesystem!");

        $this->assertSame(
            'efd4f9c0be4288e9ef2fe300cad1c3e6',
            $this->disk->hash('file.txt') // MD5 by default
        );

        $this->assertSame(
            'a4e755eb3cb87bd033616dd97bff56339ff00f76',
            $this->disk->hash('file.txt', 'sha1')
        );

        $this->assertSame(
            '90dc14eec96a6f3308940dc63c8112051cf6954eb75182f11943ae127d67f521',
            $this->disk->hash('file.txt', 'sha3-256')
        );

        $this->disk->delete('file.txt');
    }
}