<?php

namespace Imhotep\Tests\Cache\Stores;

use Imhotep\Cache\Stores\ArrayStore;

class ArrayStoreTest extends StoreTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new ArrayStore();
    }

    protected function tearDown(): void
    {
        $this->store->flush();

        parent::tearDown();
    }
}