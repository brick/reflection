<?php

declare(strict_types=1);

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
    private static function assertResolve(string $expectedFqcn, string $type) : void
    {
        $resolver = new ImportResolver(new \ReflectionClass(self::class));
        self::assertSame($expectedFqcn, $resolver->resolve($type));
    }

    public function testFullyQualifiedClassName() : void
    {
        self::assertResolve(ImportResolver::class, '\\' . ImportResolver::class);
        self::assertResolve(Tools::class, '\\' . Tools::class);
        self::assertResolve(self::class, '\\' . self::class);
    }

    public function testClassInSameNamespace() : void
    {
        self::assertResolve(__NAMESPACE__ . '\A', 'A');
        self::assertResolve(__NAMESPACE__ . '\A\B', 'A\B');
        self::assertResolve(__CLASS__, 'ImportResolverTest');
    }

    public function testImport() : void
    {
        self::assertResolve(ImportResolver::class, 'ImportResolver');
        self::assertResolve(ImportResolver::class . '\A', 'ImportResolver\A');
        self::assertResolve(ImportResolver::class . '\A\B', 'ImportResolver\A\B');
    }

    public function testAliasedImport() : void
    {
        self::assertResolve(Tools::class, 'Tools');
        self::assertResolve(Tools::class . '\A', 'Tools\A');
        self::assertResolve(Tools::class . '\A\B', 'Tools\A\B');
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
