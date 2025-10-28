<?php

declare(strict_types=1);

namespace Brick\Reflection;

use Brick\Reflection\Internal\TokenParser;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use Reflector;
use RuntimeException;

use function file_get_contents;
use function sprintf;
use function strpos;
use function strtolower;
use function substr;

/**
 * Resolves class names using the rules PHP uses internally.
 *
 * @see http://www.php.net/manual/en/language.namespaces.importing.php
 */
final class ImportResolver
{
    private readonly string $namespace;

    /**
     * @var array<string, string>
     */
    private array $aliases;

    /**
     * Class constructor.
     *
     * @param Reflector $context A reflection of the context in which the types will be resolved.
     *                           The context can be a class, property, method or parameter.
     *
     * @throws InvalidArgumentException If the class or file name cannot be inferred from the context.
     */
    public function __construct(Reflector $context)
    {
        $class = $this->getDeclaringClass($context);

        if ($class === null) {
            throw $this->invalidArgumentException('declaring class', $context);
        }

        $fileName = $class->getFileName();

        if ($fileName === false) {
            throw $this->invalidArgumentException('file name', $context);
        }

        $source = @file_get_contents($fileName);

        if ($source === false) {
            throw new RuntimeException('Could not read ' . $fileName);
        }

        $parser = new TokenParser($source);

        $this->namespace = $class->getNamespaceName();
        $this->aliases = $parser->parseUseStatements($this->namespace);
    }

    /**
     * @param string $type A class or interface name.
     *
     * @return string The fully qualified class name.
     */
    public function resolve(string $type): string
    {
        $pos = strpos($type, '\\');

        if ($pos === 0) {
            return substr($type, 1); // Already fully qualified.
        }

        if ($pos === false) {
            $first = $type;
            $next = '';
        } else {
            $first = substr($type, 0, $pos);
            $next = substr($type, $pos);
        }

        $first = strtolower($first);

        if (isset($this->aliases[$first])) {
            return $this->aliases[$first] . $next;
        }

        return $this->namespace . '\\' . $type;
    }

    /**
     * Returns the ReflectionClass of the given Reflector.
     */
    private function getDeclaringClass(Reflector $reflector): ?ReflectionClass
    {
        if ($reflector instanceof ReflectionClass) {
            return $reflector;
        }

        if ($reflector instanceof ReflectionClassConstant) {
            return $reflector->getDeclaringClass();
        }

        if ($reflector instanceof ReflectionProperty) {
            return $reflector->getDeclaringClass();
        }

        if ($reflector instanceof ReflectionMethod) {
            return $reflector->getDeclaringClass();
        }

        if ($reflector instanceof ReflectionParameter) {
            return $reflector->getDeclaringClass();
        }

        return null;
    }

    private function invalidArgumentException(string $inferring, Reflector $reflector): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf(
            'Cannot infer the %s from the given %s',
            $inferring,
            $reflector::class,
        ));
    }
}
