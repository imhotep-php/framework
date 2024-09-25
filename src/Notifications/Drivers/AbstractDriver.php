<?php

namespace Imhotep\Notifications\Drivers;

use Imhotep\Contracts\Notifications\Notification;

abstract class AbstractDriver
{
    abstract public function send($recipient, Notification $notification): bool;
}