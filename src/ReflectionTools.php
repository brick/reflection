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
     * Returns reflections of all the non-static methods that make up one object.
     *
     * This returns the same methods as ReflectionClass::getMethods(),
     * plus the private methods of all parent classes.
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
            foreach ($hClass->getMethods() as $method) {
                if ($method->isStatic()) {
                    continue;
                }

                if ($hClass === $class || $method->isPrivate()) {
                    $methods[] = $method;
                }
            }
        }

        return $methods;
    }

    /**
     * Returns reflections of all the non-static properties that make up one object.
     *
     * This returns the same properties as ReflectionClass::getProperties(),
     * plus the private properties of all parent classes.
     *
     * @param \ReflectionClass $class
     *
     * @return \ReflectionProperty[]
     */
    public function getClassProperties(\ReflectionClass $class) : array
    {
        $classes = $this->getClassHierarchy($class);

        $properties = [];

        foreach ($classes as $hClass) {
            foreach ($hClass->getProperties() as $property) {
                if ($property->isStatic()) {
                    continue;
                }

                if ($hClass === $class || $property->isPrivate()) {
                    $properties[] = $property;
                }
            }
        }

        return $properties;
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
    public function getFunctionParameterTypes(\ReflectionFunctionAbstract $function) : array
    {
        return $this->cache(__FUNCTION__, $function, function() use ($function) {
            preg_match_all('/@param\s+(\S+)\s+\$(\S+)/', $function->getDocComment(), $matches, PREG_SET_ORDER);

            $types = [];
            foreach ($matches as $match) {
                $types[$match[2]] = explode('|', $match[1]);
            }

            return $types;
        });
    }

    /**
     * Returns the types documented on a parameter.
     *
     * @param \ReflectionParameter $parameter
     *
     * @return array
     */
    public function getParameterTypes(\ReflectionParameter $parameter) : array
    {
        $name = $parameter->getName();
        $function = $parameter->getDeclaringFunction();
        $types = $this->getFunctionParameterTypes($function);

        return isset($types[$name]) ? $types[$name] : [];
    }

    /**
     * Returns the types documented on a property.
     *
     * @param \ReflectionProperty $property
     *
     * @return array
     */
    public function getPropertyTypes(\ReflectionProperty $property) : array
    {
        if (preg_match('/@var\s+(\S+)/', $property->getDocComment(), $matches) !== 1) {
            return [];
        }

        return explode('|', $matches[1]);
    }

    /**
     * Returns the fully qualified class name documented for the given property.
     *
     * @param \ReflectionProperty $property
     *
     * @return string|null The class name, or null if not available.
     */
    public function getPropertyClass(\ReflectionProperty $property) : ?string
    {
        $types = $this->getPropertyTypes($property);

        if (count($types) === 1) {
            $type = $types[0];

            if ($type[0] === '\\') {
                return substr($type, 1);
            }
        }

        return null;
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

            if ($parameter->allowsNull() && ! $parameter->isDefaultValueAvailable()) {
                $result .= '?';
            }

            if (null !== $type = $parameter->getType()) {
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
     * Caches the output of a worker function.
     *
     * @param string   $method   The name of the calling method.
     * @param object   $object   The object to use as the cache key.
     * @param callable $callback The callback that does the actual work.
     *
     * @return mixed The callback return value, potentially cached.
     */
    private function cache(string $method, $object, callable $callback)
    {
        $hash = spl_object_hash($object);

        if (! isset($this->cache[$method][$hash])) {
            $this->cache[$method][$hash] = $callback();
        }

        return $this->cache[$method][$hash];
    }
}
