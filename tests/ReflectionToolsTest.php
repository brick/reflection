<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests;

use Brick\Reflection\ReflectionTools;

use Brick\Reflection\Tests\Attributes\ExpectFunctionSignature;
use Brick\Reflection\Tests\Classes\PHP80;
use Brick\Reflection\Tests\Classes\PHP81;
use Brick\Reflection\Tests\Classes\PHP82;
use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

/**
 * Unit tests for class ReflectionTools.
 */
class ReflectionToolsTest extends TestCase
{
    public function testGetMethodsDoesNotReturnStaticMethods() : void
    {
        $class = new \ReflectionClass(__NAMESPACE__ . '\\S');
        $methods = (new ReflectionTools)->getClassMethods($class);

        self::assertCount(0, $methods);
    }

    /**
     * @return void
     */
    public function testGetPropertiesDoesNotReturnStaticProperties() : void
    {
        $class = new \ReflectionClass(__NAMESPACE__ . '\\S');
        $properties = (new ReflectionTools)->getClassProperties($class);

        self::assertCount(0, $properties);
    }

    /**
     * @dataProvider hierarchyTestProvider
     *
     * @param string $class
     * @param array  $expected
     */
    public function testGetMethods(string $class, array $expected) : void
    {
        $class = new \ReflectionClass(__NAMESPACE__ . '\\' . $class);
        $methods = (new ReflectionTools)->getClassMethods($class);

        $actual = [];

        foreach ($methods as $method) {
            $actual[] = [
                $method->getDeclaringClass()->getShortName(),
                $method->getName()
            ];
        }

        self::assertEquals($expected, $actual);
    }

    /**
     * @dataProvider hierarchyTestProvider
     *
     * @param string $class
     * @param array  $expected
     */
    public function testGetProperties(string $class, array $expected) : void
    {
        $class = new \ReflectionClass(__NAMESPACE__ . '\\' . $class);
        $properties = (new ReflectionTools)->getClassProperties($class);

        $actual = [];

        foreach ($properties as $property) {
            $actual[] = [
                $property->getDeclaringClass()->getShortName(),
                $property->getName()
            ];
        }

        self::assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function hierarchyTestProvider() : array
    {
        return [
            ['A', [
                ['A', 'a'],
                ['A', 'b'],
                ['A', 'c'],
            ]],
            ['B', [
                ['A', 'a'],
                ['B', 'a'],
                ['B', 'b'],
                ['B', 'c']
            ]],
            ['C', [
                ['A', 'a'],
                ['B', 'a'],
                ['C', 'a'],
                ['C', 'b'],
                ['C', 'c']
            ]],
            ['X', [
                ['X', 'a'],
                ['X', 'b'],
                ['X', 'c'],
            ]],
            ['Y', [
                ['X', 'a'],
                ['X', 'b'],
                ['X', 'c'],
                ['Y', 'd'],
                ['Y', 'e'],
                ['Y', 'f'],
            ]]
        ];
    }

    /**
     * @dataProvider providerExportFunction
     */
    public function testExportFunctionSignature(ReflectionMethod $method, string $expectedFunctionSignature) : void
    {
        $tools = new ReflectionTools();
        self::assertSame($expectedFunctionSignature, $tools->exportFunctionSignature($method));
    }

    public function providerExportFunction() : Generator
    {
        $classes = [
            PHP80::class,
        ];

        if (PHP_VERSION_ID >= 80100) {
            $classes[] = PHP81::class;
        }

        if (PHP_VERSION_ID >= 80200) {
            $classes[] = PHP82::class;
        }

        foreach ($classes as $class) {
            $reflectionClass = new ReflectionClass($class);
            foreach ($reflectionClass->getMethods() as $reflectionMethod) {
                $reflectionAttributes = $reflectionMethod->getAttributes(ExpectFunctionSignature::class);

                $expectFunctionSignatures = array_map(
                    fn (ReflectionAttribute $reflectionAttribute) => $reflectionAttribute->newInstance(),
                    $reflectionAttributes,
                );

                $expectFunctionSignature = $this->matchExpectFunctionSignature($expectFunctionSignatures);

                yield [$reflectionMethod, $expectFunctionSignature->functionSignature];
            }
        }
    }

    /**
     * @param ExpectFunctionSignature[] $expectFunctionSignatures
     */
    private function matchExpectFunctionSignature(array $expectFunctionSignatures): ExpectFunctionSignature
    {
        foreach ($expectFunctionSignatures as $expectFunctionSignature) {
            if ($this->phpMatchesVersionConstraint($expectFunctionSignature->phpVersionConstraint)) {
                return $expectFunctionSignature;
            }
        }

        throw new Exception('No ExpectFunctionSignature attribute found matching current PHP version');
    }

    private function phpMatchesVersionConstraint(?string $versionConstraint): bool
    {
        if ($versionConstraint === null) {
            return true;
        }

        $versionConstraintParts = explode(' ', $versionConstraint);

        if (count($versionConstraintParts) !== 2) {
            throw new Exception("Invalid version constraint: $versionConstraint");
        }

        [$operator, $version] = $versionConstraintParts;

        if (! ctype_digit($version)) {
            throw new Exception("Invalid version: $version");
        }

        $version = (int) $version;

        $allowedComparisonValues = match ($operator) {
            '<' => [-1],
            '<=' => [-1, 0],
            '=' => [0],
            '>=' => [0, 1],
            '>' => [1],
        };

        $comparisonValue = (PHP_VERSION_ID <=> $version);

        return in_array($comparisonValue, $allowedComparisonValues, true);
    }
}

class A
{
    private $a;
    protected $b;
    public $c;

    private function a() {}
    protected function b() {}
    public function c() {}
}

class B extends A
{
    private $a;
    protected $b;
    public $c;

    private function a() {}
    protected function b() {}
    public function c() {}
}

class C extends B
{
    private $a;
    protected $b;
    public $c;

    private function a() {}
    protected function b() {}
    public function c() {}
}

class S
{
    private static $a;
    protected static $b;
    public static $c;

    private static function a() {}
    protected static function b() {}
    public static function c() {}
}

class X
{
    public $a;
    public $b;
    public $c;

    public function a() {}
    public function b() {}
    public function c() {}
}

class Y extends X
{
    public $d;
    public $e;
    public $f;

    public function d() {}
    public function e() {}
    public function f() {}
}
