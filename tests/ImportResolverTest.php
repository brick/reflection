<?php

namespace Brick\Reflection\Tests;

use Brick\Reflection\ImportResolver;
use Brick\Reflection\ReflectionTools as Tools;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ImportResolver class.
 */
class ImportResolverTest extends TestCase
{
    /**
     * @param string $expectedFqcn
     * @param string $type
     *
     * @return void
     */
    private function assertResolve($expectedFqcn, $type)
    {
        $resolver = new ImportResolver(new \ReflectionObject($this));
        $this->assertSame($expectedFqcn, $resolver->resolve($type));
    }

    public function testFullyQualifiedClassName()
    {
        $this->assertResolve(ImportResolver::class, '\\' . ImportResolver::class);
        $this->assertResolve(Tools::class, '\\' . Tools::class);
        $this->assertResolve(self::class, '\\' . self::class);
    }

    public function testClassInSameNamespace()
    {
        $this->assertResolve(__NAMESPACE__ . '\A', 'A');
        $this->assertResolve(__NAMESPACE__ . '\A\B', 'A\B');
        $this->assertResolve(__CLASS__, 'ImportResolverTest');
    }

    public function testImport()
    {
        $this->assertResolve(ImportResolver::class, 'ImportResolver');
        $this->assertResolve(ImportResolver::class . '\A', 'ImportResolver\A');
        $this->assertResolve(ImportResolver::class . '\A\B', 'ImportResolver\A\B');
    }

    public function testAliasedImport()
    {
        $this->assertResolve(Tools::class, 'Tools');
        $this->assertResolve(Tools::class . '\A', 'Tools\A');
        $this->assertResolve(Tools::class . '\A\B', 'Tools\A\B');
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Cannot infer the file name from the given ReflectionObject
     */
    public function testConstructorWithInvalidInferFileNameShouldThrowInvalidArgumentException()
    {
        $resolver = new ImportResolver(new \ReflectionObject(new \Exception));
    }

    public function testConstructorWithReflectionProperty()
    {
        $resolver = new ImportResolver(new \ReflectionProperty(ReflectionTarget::class, 'foo'));

        $this->assertSame(ReflectionTarget::class, $resolver->resolve('ReflectionTarget'));
    }

    public function testConstructorWithReflectionMethod()
    {
        $resolver = new ImportResolver(new \ReflectionMethod(ReflectionTarget::class, 'publicStaticMethod'));

        $this->assertSame(ReflectionTarget::class, $resolver->resolve('ReflectionTarget'));
    }

    public function testConstructorWithReflectionParameter()
    {
        $resolver = new ImportResolver(new \ReflectionParameter([
            ReflectionTarget::class, 'privateFunc',
        ], 'str'));

        $this->assertSame(ReflectionTarget::class, $resolver->resolve('ReflectionTarget'));
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Cannot infer the declaring class from the given ReflectionFunction
     */
    public function testConstructorWithReflectionFunctionThrowsException()
    {
        $resolver = new ImportResolver(new \ReflectionFunction('Brick\Reflection\Tests\reflectedFunc'));
    }
}
