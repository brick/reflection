<?php

declare(strict_types=1);

namespace Brick\Reflection\Tests;

use Brick\Reflection\ReflectionTools;

use Brick\Reflection\Tests\Classes\PHP72;
use Brick\Reflection\Tests\Classes\PHP74;
use Brick\Reflection\Tests\Classes\PHP80;
use Generator;
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
        $function = new \ReflectionMethod($method);
        self::assertSame($expected, $tools->exportFunction($function, $excludeModifiers));
    }

    public function providerExportFunction() : Generator
    {
        $tests = [
            [Export::class . '::a', 0, 'final public function a(?\Brick\Reflection\Tests\A $a, \stdClass $b)'],
            [Export::class . '::b', 0, 'public static function b(array & $a, callable $b = NULL) : \PDO'],
            [Export::class . '::b', \ReflectionMethod::IS_STATIC, 'public function b(array & $a, callable $b = NULL) : \PDO'],
            [Export::class . '::c', 0, 'abstract protected function c(int $a = 1, float $b = 0.5, string $c = \'test\', $eol = PHP_EOL, \StdClass ...$objects) : ?string'],
            [Export::class . '::d', 0, 'private function d(?int $a, ?int $b) : ?string'],

            // there does not seem to be a way to differentiate between `?int $b = NULL` and `int $b = NULL`, and PHP considers them as compatible
            [Export::class . '::e', 0, 'private function e(?int $a, int $b = NULL) : ?string'],

            [Export::class . '::f', 0, 'public function f($x)'],
        ];

        foreach ($tests as $test) {
            yield $test;
        }

        yield [PHP80::class . '::returnStatic', 0, 'public function returnStatic() : static'];
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
    public function f($x) {}
}
