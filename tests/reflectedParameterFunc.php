<?php

namespace Brick\Reflection\Tests;

/**
 * The Target Reflection function with string parameter.
 * @param string $arg
 */
function reflectedParameterFunc(string $arg)
{
    return isset($arg) ? $arg : 'test';
}
