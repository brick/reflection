<?php

declare(strict_types=1);

namespace Brick\Reflection;

/**
 * Tools for the Reflection API.
 *
 * The output of certain methods is cached, so the memory consumption of an instance of this class
 * can grow with time. To reclaim memory, one can just drop an instance of this class and replace
 * it with a fresh one at any time.
 */
class ReflectionTools
{
    /**
     * A generic cache for the output of methods.
     *
     * @var array
     */
    private $cache = [];

    /**
     * The list of built-in PHP types.
     *
     * Note that 'resource' is not included, as it's not available as a type-hint.
     * Note that 'mixed' is allowed if the PHP version is 8 or above.
     *
     * @var string[]
     */
    private $builtInTypes = [
        'array',
        'object',
        'int',
        'float',
        'string',
        'bool',
        'null',
    ];

    /**
     * ReflectionTools constructor.
     */
    public function __construct()
    {
        if (version_compare(PHP_VERSION, '8.0') >= 0) {
            $this->builtInTypes[] = 'mixed';
        }
    }

    /**
     * Returns reflections of all the non-static methods that make up one object.
     *
     * Like ReflectionClass::getMethods(), this method:
     *
     * - does not return overridden protected or public class methods, and only return the overriding one;
     * - returns methods inside a class in the order they are declared.
     *
     * Unlike ReflectionClass::getMethods(), this method:
     *
     * - returns the private methods of parent classes;
     * - returns methods in hierarchical order: methods from parent classes are returned first.
     *
     * @param \ReflectionClass $class
     *
     * @return \ReflectionMethod[]
     */
    public function getClassMethods(\ReflectionClass $class) : array
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
     * - does not return overridden protected or public class properties, and only return the overriding one;
     * - returns properties inside a class in the order they are declared.
     *
     * Unlike ReflectionClass::getProperties(), this method:
     *
     * - returns the private properties of parent classes;
     * - returns properties in hierarchical order: properties from parent classes are returned first.
     *
     * @param \ReflectionClass $class
     *
     * @return \ReflectionProperty[]
     */
    public function getClassProperties(\ReflectionClass $class) : array
    {
        $classes = $this->getClassHierarchy($class);

        /** @var \ReflectionProperty[] $properties */
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
     * @param \ReflectionClass $class
     *
     * @return \ReflectionClass[]
     */
    public function getClassHierarchy(\ReflectionClass $class) : array
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
     *
     * @param callable $function
     *
     * @return \ReflectionFunctionAbstract
     */
    public function getReflectionFunction(callable $function) : \ReflectionFunctionAbstract
    {
        if (is_array($function)) {
            return new \ReflectionMethod($function[0], $function[1]);
        }

        if (is_object($function) && ! $function instanceof \Closure) {
            return new \ReflectionMethod($function, '__invoke');
        }

        return new \ReflectionFunction($function);
    }

    /**
     * Returns the types documented on a parameter.
     *
     * If the parameter is typed, the values returned by reflection will be used.
     * Otherwise, this method will look for a phpdoc `@param` annotation in the doc comment of the declaring function.
     *
     * Class names are returned using their FQCN (including namespace).
     *
     * @param \ReflectionParameter $parameter
     *
     * @return string[]
     */
    public function getParameterTypes(\ReflectionParameter $parameter) : array
    {
        $types = $this->getReflectionTypes($parameter->getType());

        if ($types !== null) {
            return $types;
        }

        $name = $parameter->getName();
        $function = $parameter->getDeclaringFunction();
        $types = $this->getFunctionParameterTypes($function);

        if (! isset($types[$name])) {
            return [];
        }

        $types = $types[$name];

        return $this->resolvePhpDocTypes($types, $function);
    }

    /**
     * Returns the types documented on a property.
     *
     * If the property is typed (PHP 7.4+), the values returned by reflection will be used.
     * Otherwise, this method will look for a phpdoc `@var` annotation in the doc comment.
     *
     * Class names are returned using their FQCN (including namespace).
     *
     * @param \ReflectionProperty $property
     *
     * @return string[]
     */
    public function getPropertyTypes(\ReflectionProperty $property) : array
    {
        if (version_compare(PHP_VERSION, '7.4') >= 0) {
            $types = $this->getReflectionTypes($property->getType());

            if ($types !== null) {
                return $types;
            }
        }

        $docComment = $property->getDocComment();

        if ($docComment === false) {
            return [];
        }

        if (preg_match('/@var\s+(\S+)/', $docComment, $matches) !== 1) {
            return [];
        }

        $types = explode('|', $matches[1]);

        return $this->resolvePhpDocTypes($types, $property);
    }

    /**
     * Returns a meaningful name for the given function, including the class name if it is a method.
     *
     * Example for a method: Namespace\Class::method
     * Example for a function: strlen
     * Example for a closure: {closure}
     *
     * @param \ReflectionFunctionAbstract $function
     *
     * @return string
     */
    public function getFunctionName(\ReflectionFunctionAbstract $function) : string
    {
        if ($function instanceof \ReflectionMethod) {
            return $function->getDeclaringClass()->getName() . '::' . $function->getName();
        }

        return $function->getName();
    }

    /**
     * Exports the function signature.
     *
     * @param \ReflectionFunctionAbstract $function         The function to export.
     * @param int                         $excludeModifiers An optional bitmask of modifiers to exclude.
     *
     * @return string
     */
    public function exportFunction(\ReflectionFunctionAbstract $function, int $excludeModifiers = 0) : string
    {
        $result = '';

        if ($function instanceof \ReflectionMethod) {
            $modifiers = $function->getModifiers();
            $modifiers &= ~ $excludeModifiers;

            foreach (\Reflection::getModifierNames($modifiers) as $modifier) {
                $result .= $modifier . ' ';
            }
        }

        $result .= 'function ' . $function->getShortName();
        $result .= '(' . $this->exportFunctionParameters($function) . ')';

        if (null !== $returnType = $function->getReturnType()) {
            $result .= ' : ';

            if ($returnType->allowsNull()) {
                $result .= '?';
            }

            if (! $returnType->isBuiltin()) {
                $result .= '\\';
            }

            $result .= $returnType->getName();
        }

        return $result;
    }

    /**
     * @param \ReflectionFunctionAbstract $function
     *
     * @return string
     */
    public function exportFunctionParameters(\ReflectionFunctionAbstract $function) : string
    {
        $result = '';

        foreach ($function->getParameters() as $key => $parameter) {
            if ($key !== 0) {
                $result .= ', ';
            }

            if (null !== $type = $parameter->getType()) {
                if ($parameter->allowsNull() && ! $parameter->isDefaultValueAvailable()) {
                    $result .= '?';
                }

                if (! $type->isBuiltin()) {
                    $result .= '\\';
                }

                $result .= $type->getName() . ' ';
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
                    $result .= ' = ' . $parameter->getDefaultValueConstantName();
                } else {
                    $result .= ' = ' . var_export($parameter->getDefaultValue(), true);
                }
            }
        }

        return $result;
    }

    /**
     * Caches the output of a function.
     *
     * @param string      $method   The name of the calling method.
     * @param string|null $key      A method-specific cache key, or null if the output cannot be cached.
     * @param \Closure    $callback The callback function that does the actual work.
     *
     * @return mixed The callback return value, potentially cached.
     */
    private function cache(string $method, ?string $key, \Closure $callback)
    {
        if ($key === null) {
            return $callback();
        }

        if (isset($this->cache[$method][$key])) {
            return $this->cache[$method][$key];
        }

        return $this->cache[$method][$key] = $callback();
    }

    /**
     * @param string $type The *lowercase* type.
     *
     * @return bool
     */
    private function isBuiltInType(string $type) : bool
    {
        return in_array($type, $this->builtInTypes, true);
    }

    /**
     * Filters a list of ReflectionProperty or ReflectionMethod objects.
     *
     * This method removes overridden properties, while keeping original order.
     *
     * Note: ReflectionProperty and ReflectionObject do not explicitly share the same interface, but for the current
     * purpose they share the same set of methods, and as such are duck typed here.
     *
     * @param \ReflectionProperty[]|\ReflectionMethod[] $reflectors
     *
     * @return \ReflectionProperty[]|\ReflectionMethod[]
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

    /**
     * Returns an associative array of the types documented on a function.
     *
     * The keys are the parameter names, and values the documented types as an array.
     * This method does not check that parameter names actually exist,
     * so documented types might not match the function parameter names.
     *
     * @param \ReflectionFunctionAbstract $function
     *
     * @return array
     */
    private function getFunctionParameterTypes(\ReflectionFunctionAbstract $function) : array
    {
        if ($function->isClosure()) {
            $cacheKey = null;
        } else {
            $cacheKey = $this->getFunctionName($function);
        }

        return $this->cache(__FUNCTION__, $cacheKey, static function() use ($function) {
            $docComment = $function->getDocComment();

            if ($docComment === false) {
                return [];
            }

            preg_match_all('/@param\s+(\S+)\s+\$(\S+)/', $docComment, $matches, PREG_SET_ORDER);

            $types = [];

            foreach ($matches as $match) {
                $types[$match[2]] = explode('|', $match[1]);
            }

            return $types;
        });
    }

    /**
     * Returns the types as returned by reflection, or null if no type is set.
     *
     * @param \ReflectionType|null $type
     *
     * @return string[]|null
     */
    private function getReflectionTypes(?\ReflectionType $type) : ?array
    {
        if ($type instanceof \ReflectionNamedType) { // PHP 7.4+
            $types = [$type->getName()];

            if ($type->allowsNull()) {
                $types[] = 'null';
            }

            return $types;
        }

        if ($type instanceof \ReflectionUnionType) { // PHP 8.0+
            return array_map(function (\ReflectionNamedType $type) {
                return $type->getName();
            }, $type->getTypes());
        }

        return null;
    }

    /**
     * Resolves the given phpdoc types documented with `@var` or `@param`:
     *
     * - built-in types are lowercased
     * - class & interface names are resolved to their FQCN
     * - the resulting array is deduplicated
     *
     * @param string[]   $types   The types to resolve.
     * @param \Reflector $context The context the types are resolved relative to.
     *
     * @return array
     */
    private function resolvePhpDocTypes(array $types, \Reflector $context) : array
    {
        // instantiate the ImportResolver just-in-time, if required
        $importResolver = null;

        $result = [];

        foreach ($types as $type) {
            $typeLower = strtolower($type);

            if ($this->isBuiltInType($typeLower)) {
                $result[] = $typeLower;
                continue;
            }

            if ($importResolver === null) {
                $importResolver = new ImportResolver($context);
            }

            $result[] = $importResolver->resolve($type);
        }

        return array_values(array_unique($result));
    }
}
