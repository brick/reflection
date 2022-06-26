<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ExpectFunctionSignature
{
    public function __construct(
        public string $functionSignature,
    ) {
    }
}
