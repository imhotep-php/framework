<?php

namespace Imhotep\Contracts\Notifications;

interface Notification
{
    public function via($recipient): array;
}