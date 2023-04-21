<?php

declare(strict_types=1);

namespace Imhotep\Events;

trait useDispatcher
{
    public static function dispatch(){
        return event(new static(...func_get_args()));
    }
}