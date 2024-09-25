<?php

namespace Imhotep\Contracts\Notifications;

interface Message
{
    public function toArray(): array;
}