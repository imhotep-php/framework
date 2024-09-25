<?php

namespace Imhotep\Queue\Drivers\Null;

use Imhotep\Contracts\Queue\Connector;
use Imhotep\Contracts\Queue\Queue;

class NullConnector implements Connector
{
    public function connect(): Queue
    {
        return new NullQueue();
    }
}