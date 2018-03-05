<?php

namespace Brick\Reflection\Tests;

/**
 * The Reflection Target class.
 */
class ReflectionTarget
{
    /**
     * @param string $foo
     */
    private $foo;

    /**
     * @var string $bar
     */
    private $bar;

    /**
     * @var \\Exception $barWithType
     */
    private $barWithType;

    public function __construct()
    {
        $this->foo = 'foo';
        $this->bar = 'bar';
    }

    /**
     * @param  string $str
     * @return string $str
     */
    private function privateFunc(string $str)
    {
        return $str;
    }

    /**
     * @return void
     */
    public static function publicStaticMethod()
    {
        return 'publicStaticMethod';
    }
}