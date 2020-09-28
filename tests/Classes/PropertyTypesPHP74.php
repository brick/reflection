<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

class PropertyTypesPHP74 extends PropertyTypesPHP72
{
    public string $b;

    public ?\PDO $c;
}
