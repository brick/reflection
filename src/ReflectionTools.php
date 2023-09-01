<?php

declare(strict_types=1);

namespace Brick\Reflection;

use Brick\VarExporter\VarExporter;
use Closure;
use Exception;
use Reflection;
use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

/**
 * Tools for the Reflection API.
 */
class ReflectionTools
{
    /**
     * Returns reflections of all the non-static methods that make up one object.
     *
     * Like ReflectionClass::getMethods(), this method:
     *
     * - does not return overridden protected or public class methods, and only returns the overriding one;
     * - returns methods inside a class in the order they are declared.
     *
     * Unlike ReflectionClass::getMethods(), this method:
     *
     * - returns the private methods of parent classes;
     * - returns methods in hierarchical order: methods from parent classes are returned first.
     *
     * @return ReflectionMethod[]
     */
    public function getClassMethods(ReflectionClass $class) : array
    {
        $classes = $this->getClassHierarchy($class);

        $methods = [];

        foreach ($classes as $hClass) {
            $hClassName = $hClass->getName();

            foreach ($hClass->getMethods() as $method) {
                if ($method->isStatic()) {
                    // exclude static methods
                    continue;
                }

                if ($method->getDeclaringClass()->getName() !== $hClassName) {
                    // exclude inherited methods
                    continue;
                }

                $methods[] = $method;
            }
        }

        return $this->filterReflectors($methods);
    }

    /**
     * Returns reflections of all the non-static properties that make up one object.
     *
     * Like ReflectionClass::getProperties(), this method:
     *
     * - does not return overridden protected or public class properties, and only returns the overriding one;
     * - returns properties inside a class in the order they are declared.
     *
     * Unlike ReflectionClass::getProperties(), this method:
     *
     * - returns the private properties of parent classes;
     * - returns properties in hierarchical order: properties from parent classes are returned first.
     *
     * @return ReflectionProperty[]
     */
    public function getClassProperties(ReflectionClass $class) : array
    {
        $classes = $this->getClassHierarchy($class);

        /** @var ReflectionProperty[] $properties */
        $properties = [];

        foreach ($classes as $hClass) {
            $hClassName = $hClass->getName();

            foreach ($hClass->getProperties() as $property) {
                if ($property->isStatic()) {
                    // exclude static properties
                    continue;
                }

                if ($property->getDeclaringClass()->getName() !== $hClassName) {
                    // exclude inherited properties
                    continue;
                }

                $properties[] = $property;
            }
        }

        return $this->filterReflectors($properties);
    }

    /**
     * Returns the hierarchy of classes, starting from the first ancestor and ending with the class itself.
     *
     * @return ReflectionClass[]
     */
    public function getClassHierarchy(ReflectionClass $class) : array
    {
        $classes = [];

        while ($class) {
            $classes[] = $class;
            $class = $class->getParentClass();
        }

        return array_reverse($classes);
    }

    /**
     * Returns a reflection object for any callable.
     */
    public function getReflectionFunction(callable $function) : ReflectionFunctionAbstract
    {
        if (is_array($function)) {
            return new ReflectionMethod($function[0], $function[1]);
        }

        if ($function instanceof Closure) {
            return new ReflectionFunction($function);
        }

        if (is_object($function)) {
            return new ReflectionMethod($function, '__invoke');
        }

        return new ReflectionFunction($function);
    }

    /**
     * Returns a meaningful name for the given function, including the class name if it is a method.
     *
     * Example for a method: Namespace\Class::method
     * Example for a function: strlen
     * Example for a closure: {closure}
     */
    public function getFunctionName(ReflectionFunctionAbstract $function) : string
    {
        if ($function instanceof ReflectionMethod) {
            return $function->getDeclaringClass()->getName() . '::' . $function->getName();
        }

        return $function->getName();
    }

    /**
     * Exports the function signature.
     *
     * @param ReflectionFunctionAbstract $function         The function to export.
     * @param int                        $excludeModifiers An optional bitmask of modifiers to exclude.
     */
    public function exportFunctionSignature(ReflectionFunctionAbstract $function, int $excludeModifiers = 0) : string
    {
        $result = '';

        if ($function instanceof ReflectionMethod) {
            $modifiers = $function->getModifiers();
            $modifiers &= ~ $excludeModifiers;

            foreach (Reflection::getModifierNames($modifiers) as $modifier) {
                $result .= $modifier . ' ';
            }
        }

        $result .= 'function ';

        if ($function->returnsReference()) {
            $result .= '& ';
        }

        $result .= $function->getShortName();
        $result .= '(' . $this->exportFunctionParameters($function) . ')';

        if (null !== $returnType = $function->getReturnType()) {
            $result .= ': ' . $this->exportType($returnType);
        }

        return $result;
    }

    private function exportFunctionParameters(ReflectionFunctionAbstract $function) : string
    {
        $result = '';

        foreach ($function->getParameters() as $key => $parameter) {
            if ($key !== 0) {
                $result .= ', ';
            }

            if (null !== $type = $parameter->getType()) {
                $result .= $this->exportType($type) . ' ';
            }

            if ($parameter->isPassedByReference()) {
                $result .= '& ';
            }

            if ($parameter->isVariadic()) {
                $result .= '...';
            }

            $result .= '$' . $parameter->getName();

            if ($parameter->isDefaultValueAvailable()) {
                if ($parameter->isDefaultValueConstant()) {
                    $result .= ' = ' . '\\' . $parameter->getDefaultValueConstantName();
                } else {
                    $result .= ' = ' . VarExporter::export($parameter->getDefaultValue(), VarExporter::INLINE_ARRAY);
                }
            }
        }

        return $result;
    }

    /**
     * @psalm-suppress RedundantCondition https://github.com/vimeo/psalm/pull/8201
     */
    private function exportType(ReflectionType $type, bool $inUnion = false): string
    {
        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(
                fn (ReflectionType $type) => $this->exportType($type, true),
                $type->getTypes(),
            ));
        }

        if ($type instanceof ReflectionIntersectionType) {
            /** @psalm-suppress MixedArgument (ReflectionIntersectionType::getTypes() is not understood yet) */
            $result = implode('&', array_map(
                fn (ReflectionType $type) => $this->exportType($type),
                $type->getTypes(),
            ));

            return $inUnion ? "($result)" : $result;
        }

        if (! $type instanceof ReflectionNamedType) {
            throw new Exception('Unsupported ReflectionType class: ' . get_class($type));
        }

        $result = '';

        if ($type->allowsNull() && $type->getName() !== 'mixed' && $type->getName() !== 'null') {
            $result .= '?';
        }

        if (! $type->isBuiltin() && $type->getName() !== 'self' && $type->getName() !== 'static') {
            $result .= '\\';
        }

        $result .= $type->getName();

        return $result;
    }

    /**
     * Filters a list of ReflectionProperty or ReflectionMethod objects.
     *
     * This method removes overridden properties, while keeping original order.
     *
     * @template T of ReflectionProperty|ReflectionMethod
     *
     * @param T[] $reflectors
     *
     * @return T[]
     */
    private function filterReflectors(array $reflectors) : array
    {
        $filteredReflectors = [];

        foreach ($reflectors as $index => $reflector) {
            if ($reflector->isPrivate()) {
                $filteredReflectors[] = $reflector;
                continue;
            }

            foreach ($reflectors as $index2 => $reflector2) {
                if ($index2 <= $index) {
                    continue;
                }

                if ($reflector->getName() === $reflector2->getName()) {
                    // overridden
                    continue 2;
                }
            }

            $filteredReflectors[] = $reflector;
        }

        return $filteredReflectors;
    }
}
