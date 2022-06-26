<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

class PHP80
{
    public function returnStatic(): static
    {
    }
}
