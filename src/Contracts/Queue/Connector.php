<?php

namespace Imhotep\Contracts\Queue;

interface Connector
{
    public function connect(): Queue;
}