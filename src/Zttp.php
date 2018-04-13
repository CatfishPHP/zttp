<?php

namespace Zttp;

class Zttp
{
    /**
     * @param string $method
     * @param array  $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        return PendingZttpRequest::new()->{$method}(...$args);
    }
}
