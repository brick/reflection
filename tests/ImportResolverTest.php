<?php

namespace Brick\Tests\Reflection;

use Brick\Reflection\ImportResolver;
use Brick\Reflection\ReflectionTools as Tools;

/**
 * Unit tests for the ImportResolver class.
 */
class ImportResolverTest extends \PHPUnit_Framework_TestCase
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
}
