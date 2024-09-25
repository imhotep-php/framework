<?php

namespace Imhotep\Queue\Drivers\Sync;

use Imhotep\Contracts\Queue\Connector;
use Imhotep\Contracts\Queue\Queue;

class SyncConnector implements Connector
{
    public function connect(): Queue
    {
        return new SyncQueue();
    }
}