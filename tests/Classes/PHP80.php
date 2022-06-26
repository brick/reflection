<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests\Classes;

use Brick\Reflection\Tests\A;
use Brick\Reflection\Tests\Attributes\ExpectFunctionSignature;
use stdClass;

abstract class PHP80
{
    #[ExpectFunctionSignature('public function noParamsNoReturn()')]
    public function noParamsNoReturn() {}

    #[ExpectFunctionSignature('public function returnType() : int')]
    public function returnType(): int {}

    #[ExpectFunctionSignature('public function nullableReturnType() : ?string')]
    public function nullableReturnType(): ?string {}

    #[ExpectFunctionSignature('public function classReturnType() : \stdClass')]
    public function classReturnType(): stdClass {}

    #[ExpectFunctionSignature('public function nullableClassReturnType() : ?\Brick\Reflection\Tests\A')]
    public function nullableClassReturnType(): ?A {}

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

    #[ExpectFunctionSignature('public function nullableTypedParamWithDefaultNull(string $x = NULL)')]
    public function nullableTypedParamWithDefaultNull(?string $x = null) {}

    #[ExpectFunctionSignature('public function nullableTypedParamWithReferenceDefaultNull(string & $x = NULL)')]
    public function nullableTypedParamWithReferenceDefaultNull(?string & $x = null) {}

    #[ExpectFunctionSignature('public function nullableTypedParamWithDefaultNullOldSyntax(string $x = NULL)')]
    public function nullableTypedParamWithDefaultNullOldSyntax(string $x = null) {}

    #[ExpectFunctionSignature('public function variadics(int $a, string ...$b)')]
    public function variadics(int $a, string ...$b) {}

    #[ExpectFunctionSignature('public function nullableVariadics(int $a, ?string ...$b)')]
    public function nullableVariadics(int $a, ?string ...$b) {}

    #[ExpectFunctionSignature('public function nullableVariadicsWithReference(int $a, ?string & ...$b)')]
    public function nullableVariadicsWithReference(int $a, ?string & ...$b) {}

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
        'array $m = NULL, ' .
        'array $n = NULL, ' .
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
        array $m = null,
        ?array $n = null,
        ?stdClass & ...$objects,
    ): ?object {}
}
