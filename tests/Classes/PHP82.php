<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

use Brick\Reflection\Tests\Attributes\ExpectFunctionSignature;
use Countable;
use JsonSerializable;
use Stringable;
use Traversable;

abstract class PHP82
{
    #[ExpectFunctionSignature(
        'abstract public function dnfTypes((\Countable&\Traversable)|null $foo): ' .
        '(\Countable&\Traversable)|(\JsonSerializable&\Countable&\Stringable)|int|null'),
    ]
    abstract public function dnfTypes(
        (Countable&Traversable)|null $foo,
    ): (Countable&Traversable)|(JsonSerializable&Countable&Stringable)|int|null;
}
