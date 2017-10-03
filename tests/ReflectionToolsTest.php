<?php

namespace Brick\Reflection\Tests;

use Brick\Reflection\ReflectionTools;

/**
 * Unit tests for class ReflectionTools.
 */
class ReflectionToolsTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMethodsDoesNotReturnStaticMethods()
    {
        $class = new \ReflectionClass(__NAMESPACE__ . '\\S');
        $methods = (new ReflectionTools)->getClassMethods($class);

        $this->assertCount(0, $methods);
    }

    /**
     * @return void
     */
    public function testGetPropertiesDoesNotReturnStaticProperties()
    {
        $class = new \ReflectionClass(__NAMESPACE__ . '\\S');
        $properties = (new ReflectionTools)->getClassProperties($class);

        $this->assertCount(0, $properties);
    }

    /**
     * @dataProvider hierarchyTestProvider
     *
     * @param string $class
     * @param array  $expected
     */
    public function testGetMethods($class, array $expected)
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

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider hierarchyTestProvider
     *
     * @param string $class
     * @param array  $expected
     */
    public function testGetProperties($class, array $expected)
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

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function hierarchyTestProvider()
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
            ]]
        ];
    }

    /**
     * @dataProvider providerExportFunction
     *
     * @param string  $method
     * @param integer $excludeModifiers
     * @param string  $expected
     */
    public function testExportFunction($method, $excludeModifiers, $expected)
    {
        $tools = new ReflectionTools();
        $function = new \ReflectionMethod(__NAMESPACE__ . '\Export', $method);
        $this->assertSame($expected, $tools->exportFunction($function, $excludeModifiers));
    }

    /**
     * @return array
     */
    public function providerExportFunction()
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

abstract class Export
{
    final public function a(?A $a, \stdClass $b) {}
    public static function b(array & $a, callable $b = null) : \PDO {}
    abstract protected function c(int $a = 1, float $b = 0.5, string $c = 'test', $eol = \PHP_EOL, \StdClass ...$objects) : ?string;
    private function d(?int $a, ?int $b) : ?string {}
    private function e(?int $a, ?int $b = null) : ?string {}
}
