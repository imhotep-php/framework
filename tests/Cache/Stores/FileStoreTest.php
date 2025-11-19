<?php

namespace Imhotep\Tests\Cache\Stores;

use Imhotep\Cache\Stores\FileStore;

class FileStoreTest extends StoreTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new FileStore(__DIR__ . '/data');
    }

    protected function tearDown(): void
    {
        $this->store->flush();

        @rmdir(__DIR__ . '/data');

        parent::tearDown();
    }
}