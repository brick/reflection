<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

use Namespaced\Foo;
use stdClass;

class ParameterTypesPHP72
{
    /**
     * @param INT|string|Foo|Bar $a
     * @param \PDO|null          $b
     */
    public function x($a, $b, \stdClass $c, ?stdClass $d)
    {
    }
}
