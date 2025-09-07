<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes\PhpVersion;

use ArrayAccess;
use Brick\Reflection\Tests\Attributes\ExpectFunctionSignature;
use Countable;
use stdClass;
use Traversable;

abstract class PHP81
{
    #[ExpectFunctionSignature('final protected function returnNever(): never')]
    final protected function returnNever(): never {}

    #[ExpectFunctionSignature('abstract public function unionTypesWithFalse(string|false $a, int|false|null $b): \stdClass|false')]
    abstract public function unionTypesWithFalse(string|false $a, int|false|null $b): stdClass|false;

    #[ExpectFunctionSignature('abstract public function intersectionTypes(\Countable&\Traversable $a): \Countable&\ArrayAccess')]
    abstract public function intersectionTypes(Countable&Traversable $a): Countable&ArrayAccess;
}
