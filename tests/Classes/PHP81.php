<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

use Brick\Reflection\Tests\Attributes\ExpectFunctionSignature;

abstract class PHP81
{
    #[ExpectFunctionSignature('final protected function returnNever(): never')]
    final protected function returnNever(): never {}
}
