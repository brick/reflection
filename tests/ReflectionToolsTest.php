<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests;

use Brick\Reflection\ReflectionTools;

use Brick\Reflection\Tests\Classes\PropertyTypesPHP72;
use Brick\Reflection\Tests\Classes\PropertyTypesPHP74;
use Brick\Reflection\Tests\Classes\PropertyTypesPHP80;
use PHPUnit\Framework\TestCase;

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
     *
     * @param string $method
     * @param int    $excludeModifiers
     * @param string $expected
     */
    public function testExportFunction(string $method, int $excludeModifiers, string $expected) : void
    {
        $tools = new ReflectionTools();
        $function = new \ReflectionMethod(__NAMESPACE__ . '\Export', $method);
        self::assertSame($expected, $tools->exportFunction($function, $excludeModifiers));
    }

    /**
     * @return array
     */
    public function providerExportFunction() : array
    {
        return [
            ['a', 0, 'final public function a(?\Brick\Reflection\Tests\A $a, \stdClass $b)'],
            ['b', 0, 'public static function b(array & $a, callable $b = NULL) : \PDO'],
            ['b', \ReflectionMethod::IS_STATIC, 'public function b(array & $a, callable $b = NULL) : \PDO'],
            ['c', 0, 'abstract protected function c(int $a = 1, float $b = 0.5, string $c = \'test\', $eol = PHP_EOL, \StdClass ...$objects) : ?string'],
            ['d', 0, 'private function d(?int $a, ?int $b) : ?string'],

            // there does not seem to be a way to differentiate between `?int $b = NULL` and `int $b = NULL`, and PHP considers them as compatible
            ['e', 0, 'private function e(?int $a, int $b = NULL) : ?string'],
        ];
    }

    /**
     * @dataProvider providerGetPropertyTypes
     */
    public function testGetPropertyTypes(string $class, string $property, array $types) : void
    {
        $tools = new ReflectionTools();
        $property = new \ReflectionProperty($class, $property);
        self::assertSame($types, $tools->getPropertyTypes($property));
    }

    public function providerGetPropertyTypes() : array
    {
        $tests = [
            [PropertyTypesPHP72::class, 'a', ['int', 'string', 'Namespaced\Foo', 'Brick\Reflection\Tests\Classes\Bar']],
        ];

        if (version_compare(PHP_VERSION, '7.4') >= 0) {
            $tests = array_merge($tests, [
                [PropertyTypesPHP74::class, 'a', ['int', 'string', 'Namespaced\Foo', 'Brick\Reflection\Tests\Classes\Bar']],
                [PropertyTypesPHP74::class, 'b', ['string']],
                [PropertyTypesPHP74::class, 'c', ['PDO', 'null']],
            ]);
        }

        if (version_compare(PHP_VERSION, '8.0') >= 0) {
            $tests = array_merge($tests, [
                [PropertyTypesPHP80::class, 'a', ['int', 'string', 'Namespaced\Foo', 'Brick\Reflection\Tests\Classes\Bar']],
                [PropertyTypesPHP80::class, 'b', ['string']],
                [PropertyTypesPHP80::class, 'c', ['PDO', 'null']],
                [PropertyTypesPHP80::class, 'd', ['PDO', 'int', 'null']],
            ]);
        }

        return $tests;
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

abstract class Export
{
    final public function a(?A $a, \stdClass $b) {}
    public static function b(array & $a, callable $b = null) : \PDO {}
    abstract protected function c(int $a = 1, float $b = 0.5, string $c = 'test', $eol = \PHP_EOL, \StdClass ...$objects) : ?string;
    private function d(?int $a, ?int $b) : ?string {}
    private function e(?int $a, ?int $b = null) : ?string {}
}
