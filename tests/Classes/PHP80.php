<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

use stdClass;
use A\B;

class PHP80 extends PHP74
{
    public \PDO|int|NULL $d;

    public function y(?stdClass $a, \stdClass|B|int|string|NULL $b)
    {
    }

    public function returnStatic(): static
    {
    }
}
