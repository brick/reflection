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
    private function assertResolve(string $expectedFqcn, string $type) : void
    {
        $resolver = new ImportResolver(new \ReflectionObject($this));
        $this->assertSame($expectedFqcn, $resolver->resolve($type));
    }

    public function testFullyQualifiedClassName() : void
    {
        $this->assertResolve(ImportResolver::class, '\\' . ImportResolver::class);
        $this->assertResolve(Tools::class, '\\' . Tools::class);
        $this->assertResolve(self::class, '\\' . self::class);
    }

    public function testClassInSameNamespace() : void
    {
        $this->assertResolve(__NAMESPACE__ . '\A', 'A');
        $this->assertResolve(__NAMESPACE__ . '\A\B', 'A\B');
        $this->assertResolve(__CLASS__, 'ImportResolverTest');
    }

    public function testImport() : void
    {
        $this->assertResolve(ImportResolver::class, 'ImportResolver');
        $this->assertResolve(ImportResolver::class . '\A', 'ImportResolver\A');
        $this->assertResolve(ImportResolver::class . '\A\B', 'ImportResolver\A\B');
    }

    public function testAliasedImport() : void
    {
        $this->assertResolve(Tools::class, 'Tools');
        $this->assertResolve(Tools::class . '\A', 'Tools\A');
        $this->assertResolve(Tools::class . '\A\B', 'Tools\A\B');
    }
}
