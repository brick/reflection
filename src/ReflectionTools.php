<?php

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
     * Methods for all classes in the hierarchy are returned.
     * If protected/public methods are overridden, only the overriding method is returned.
     *
     * @param \ReflectionClass $class
     *
     * @return \ReflectionMethod[]
     */
    public function getClassMethods(\ReflectionClass $class)
    {
        $classes = $this->getClassHierarchy($class);

        /** @var \ReflectionMethod[] $methods */
        $methods = [];

        foreach ($classes as $class) {
            foreach ($class->getMethods() as $method) {
                if (! $method->isStatic()) {
                    $key = $method->isPrivate() ? ($method->class . ':' . $method->name) : $method->name;

                    if (isset($methods[$key])) {
                        // Key needs to be unset first for the new element to be on top of the array.
                        unset($methods[$key]);
                    }

                    $methods[$key] = $method;
                }
            }
        }

        return array_values($methods);
    }

    /**
     * Returns reflections of all the non-static properties that make up one object.
     *
     * Properties for all classes in the hierarchy are returned.
     * If protected/public properties are overridden, only the overriding property is returned.
     *
     * @param \ReflectionClass $class
     *
     * @return \ReflectionProperty[]
     */
    public function getClassProperties(\ReflectionClass $class)
    {
        $classes = $this->getClassHierarchy($class);

        /** @var $properties \ReflectionProperty[] */
        $properties = [];

        foreach ($classes as $class) {
            foreach ($class->getProperties() as $property) {
                if (! $property->isStatic()) {
                    $key = $property->isPrivate() ? ($property->class . ':' . $property->name) : $property->name;

                    if (isset($properties[$key])) {
                        // Key needs to be unset first for the new element to be on top of the array.
                        unset($properties[$key]);
                    }

                    $properties[$key] = $property;
                }
            }
        }

        return array_values($properties);
    }

    /**
     * Returns the hierarchy of classes, starting from the first ancestor and ending with the class itself.
     *
     * @param \ReflectionClass $class
     *
     * @return \ReflectionClass[]
     */
    public function getClassHierarchy(\ReflectionClass $class)
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
    public function getReflectionFunction(callable $function)
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
    public function getFunctionParameterTypes(\ReflectionFunctionAbstract $function)
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
    public function getParameterTypes(\ReflectionParameter $parameter)
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
    public function getPropertyTypes(\ReflectionProperty $property)
    {
        if (preg_match('/@var\s+(\S+)/', $property->getDocComment(), $matches) == 0) {
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
    public function getPropertyClass(\ReflectionProperty $property)
    {
        $types = $this->getPropertyTypes($property);

        if (count($types) == 1) {
            $type = $types[0];

            if ($type[0] == '\\') {
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
    public function getFunctionName(\ReflectionFunctionAbstract $function)
    {
        if ($function instanceof \ReflectionMethod) {
            return $function->getDeclaringClass()->getName() . '::' . $function->getName();
        }

        return $function->getName();
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
    private function cache($method, $object, callable $callback)
    {
        $hash = spl_object_hash($object);

        if (! isset($this->cache[$method][$hash])) {
            $this->cache[$method][$hash] = $callback();
        }

        return $this->cache[$method][$hash];
    }
}
