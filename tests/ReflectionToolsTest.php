<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests;

use Brick\Reflection\ReflectionTools;
use Brick\Reflection\Tests\Attributes\ExpectFunctionSignature;
use Brick\Reflection\Tests\Classes\PhpVersion\PHP80;
use Brick\Reflection\Tests\Classes\PhpVersion\PHP81;
use Brick\Reflection\Tests\Classes\PhpVersion\PHP82;
use Exception;
use Generator;
use PHPUnit\Framework\TestCase;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

use function array_map;
use function count;
use function ctype_digit;
use function explode;
use function in_array;

use const PHP_VERSION_ID;

/**
 * Unit tests for class ReflectionTools.
 */
class ReflectionToolsTest extends TestCase
{
    public function testGetMethodsDoesNotReturnStaticMethods(): void
    {
        $class = new ReflectionClass(__NAMESPACE__ . '\\Classes\\S');
        $methods = (new ReflectionTools())->getClassMethods($class);

        self::assertCount(0, $methods);
    }

    public function testGetPropertiesDoesNotReturnStaticProperties(): void
    {
        $class = new ReflectionClass(__NAMESPACE__ . '\\Classes\\S');
        $properties = (new ReflectionTools())->getClassProperties($class);

        self::assertCount(0, $properties);
    }

    /**
     * @dataProvider hierarchyTestProvider
     */
    public function testGetMethods(string $class, array $expected): void
    {
        $class = new ReflectionClass(__NAMESPACE__ . '\\Classes\\' . $class);
        $methods = (new ReflectionTools())->getClassMethods($class);

        $actual = [];

        foreach ($methods as $method) {
            $actual[] = [
                $method->getDeclaringClass()->getShortName(),
                $method->getName(),
            ];
        }

        self::assertSame($expected, $actual);
    }

    /**
     * @dataProvider hierarchyTestProvider
     */
    public function testGetProperties(string $class, array $expected): void
    {
        $class = new ReflectionClass(__NAMESPACE__ . '\\Classes\\' . $class);
        $properties = (new ReflectionTools())->getClassProperties($class);

        $actual = [];

        foreach ($properties as $property) {
            $actual[] = [
                $property->getDeclaringClass()->getShortName(),
                $property->getName(),
            ];
        }

        self::assertSame($expected, $actual);
    }

    public function hierarchyTestProvider(): array
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
                ['B', 'c'],
            ]],
            ['C', [
                ['A', 'a'],
                ['B', 'a'],
                ['C', 'a'],
                ['C', 'b'],
                ['C', 'c'],
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
            ]],
        ];
    }

    /**
     * @dataProvider providerExportFunction
     */
    public function testExportFunctionSignature(ReflectionMethod $method, string $expectedFunctionSignature): void
    {
        $tools = new ReflectionTools();
        self::assertSame($expectedFunctionSignature, $tools->exportFunctionSignature($method));
    }

    public function providerExportFunction(): Generator
    {
        $classes = [
            PHP80::class,
            PHP81::class,
        ];

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
