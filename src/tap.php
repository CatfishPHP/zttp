<?php

if (! function_exists('tap')) {
    /**
     * @param mixed    $value
     * @param \Closure $callback
     * @return mixed
     */
    function tap($value, $callback) {
        $callback($value);
        return $value;
    }
}
