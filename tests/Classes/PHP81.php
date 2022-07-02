<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

use Brick\Reflection\Tests\Attributes\ExpectFunctionSignature;
use stdClass;

abstract class PHP81
{
    #[ExpectFunctionSignature('final protected function returnNever(): never')]
    final protected function returnNever(): never {}

    #[ExpectFunctionSignature('abstract public function unionTypesWithFalse(string|false $a, int|false|null $b): \stdClass|false')]
    abstract public function unionTypesWithFalse(string|false $a, int|false|null $b): stdClass|false;
}
