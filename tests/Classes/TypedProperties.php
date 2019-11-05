<?php

namespace Brick\Reflection\Tests\Classes;

class TypedProperties {
    /**
     * @var int|string
     */
    public $a;

    public string $b;

    public ?\PDO $c;
}
