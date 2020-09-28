<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

use Namespaced\Foo;

class PropertyTypesPHP72
{
    /**
     * @var INT|string|Foo|Bar
     */
    public $a;
}
