<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

use Namespaced\Foo;

class TypedProperties {
    /**
     * @var INT|string|Foo
     */
    public $a;

    public string $b;

    public ?\PDO $c;
}
