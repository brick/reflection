<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

use Brick\Reflection\Tests\A;
use Brick\Reflection\Tests\Attributes\ExpectFunctionSignature;
use stdClass;

const TEST = 1;

abstract class PHP80
{
    #[ExpectFunctionSignature('public function noParamsNoReturnType()')]
    public function noParamsNoReturnType() {}

    #[ExpectFunctionSignature('public function returnType() : int')]
    public function returnType(): int {}

    #[ExpectFunctionSignature('public function returnNullableType() : ?string')]
    public function returnNullableType(): ?string {}

    #[ExpectFunctionSignature('public function returnClassType() : \stdClass')]
    public function returnClassType(): stdClass {}

    #[ExpectFunctionSignature('public function returnNullableClassType() : ?\Brick\Reflection\Tests\A')]
    public function returnNullableClassType(): ?A {}

    #[ExpectFunctionSignature('public function returnMixed() : mixed')]
    public function returnMixed(): mixed {}

    #[ExpectFunctionSignature('private function returnStatic() : static')]
    private function returnStatic(): static {}

    #[ExpectFunctionSignature('public function untypedParam($x)')]
    public function untypedParam($x) {}

    #[ExpectFunctionSignature('public function untypedParamWithReference(& $x)')]
    public function untypedParamWithReference(& $x) {}

    #[ExpectFunctionSignature('public function typedParam(int $x)')]
    public function typedParam(int $x) {}

    #[ExpectFunctionSignature('public function typedParamWithReference(int & $x)')]
    public function typedParamWithReference(int & $x) {}

    #[ExpectFunctionSignature('public function typedParamWithDefaultValue(int $x = 123)')]
    public function typedParamWithDefaultValue(int $x = 123) {}

    #[ExpectFunctionSignature('public function typedParamWithReferenceAndDefaultValue(int & $x = 123)')]
    public function typedParamWithReferenceAndDefaultValue(int & $x = 123) {}

    #[ExpectFunctionSignature('public function nullableTypedParam(?string $x)')]
    public function nullableTypedParam(?string $x) {}

    #[ExpectFunctionSignature('public function nullableTypedParamWithDefaultNull(?string $x = NULL)')]
    public function nullableTypedParamWithDefaultNull(?string $x = null) {}

    #[ExpectFunctionSignature('public function nullableTypedParamWithReferenceDefaultNull(?string & $x = NULL)')]
    public function nullableTypedParamWithReferenceDefaultNull(?string & $x = null) {}

    #[ExpectFunctionSignature('public function nullableTypedParamWithDefaultNullOldSyntax(?string $x = NULL)')]
    public function nullableTypedParamWithDefaultNullOldSyntax(string $x = null) {}

    #[ExpectFunctionSignature('public function nullableTypedParamWithDefaultValue(?string $x = \'hello\')')]
    public function nullableTypedParamWithDefaultValue(?string $x = 'hello') {}

    #[ExpectFunctionSignature('public function variadics(int $a, string ...$b)')]
    public function variadics(int $a, string ...$b) {}

    #[ExpectFunctionSignature('public function nullableVariadics(int $a, ?string ...$b)')]
    public function nullableVariadics(int $a, ?string ...$b) {}

    #[ExpectFunctionSignature('public function nullableVariadicsWithReference(int $a, ?string & ...$b)')]
    public function nullableVariadicsWithReference(int $a, ?string & ...$b) {}

    #[ExpectFunctionSignature('private function constantParams(string $a = \PHP_EOL, ?int $b = \Brick\Reflection\Tests\Classes\TEST)')]
    private function constantParams(string $a = \PHP_EOL, ?int $b = TEST) {}

    #[ExpectFunctionSignature(
        'private static function kitchenSink(' .
        '$a, ' .
        '& $b, ' .
        'int $c, ' .
        '?int $d, ' .
        '\stdClass $e, ' .
        '?\Brick\Reflection\Tests\A $f, ' .
        '?float $g, ' .
        '?string $h, ' .
        '?bool & $i, ' .
        'object $j, ' .
        '?object $k, ' .
        'array $l, ' .
        'mixed $m, ' .
        '?array $n = NULL, ' .
        '?array $o = NULL, ' .
        'string $p = \PHP_EOL, ' .
        'mixed $q = NULL, ' .
        '?\stdClass & ...$objects' .
        ') : ?object'
    )]
    private static function kitchenSink(
        $a,
        & $b,
        int $c,
        ?int $d,
        stdClass $e,
        ?A $f,
        ?float $g,
        ?string $h,
        ?bool & $i,
        object $j,
        ?object $k,
        array $l,
        mixed $m,
        array $n = null,
        ?array $o = null,
        string $p = \PHP_EOL,
        mixed $q = null,
        ?stdClass & ...$objects,
    ): ?object {}
}
