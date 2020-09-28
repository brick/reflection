<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

use stdClass;
use A\B;

class ParameterTypesPHP80 extends ParameterTypesPHP72
{
    public function y(?stdClass $a, \stdClass|B|int|string|NULL $b)
    {
    }
}
