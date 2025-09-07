<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

class S
{
    private static $a;
    protected static $b;
    public static $c;

    private static function a() {}
    protected static function b() {}
    public static function c() {}
}
