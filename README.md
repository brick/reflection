# Brick\Reflection

<img src="https://raw.githubusercontent.com/brick/brick/master/logo.png" alt="" align="left" height="64">

A collection of low-level tools to extend PHP reflection capabilities.

[![Build Status](https://secure.travis-ci.org/brick/reflection.svg?branch=master)](http://travis-ci.org/brick/reflection)
[![Coverage Status](https://coveralls.io/repos/github/brick/reflection/badge.svg?branch=master)](https://coveralls.io/github/brick/reflection?branch=master)
[![Latest Stable Version](https://poser.pugx.org/brick/reflection/v/stable)](https://packagist.org/packages/brick/reflection)
[![Total Downloads](https://poser.pugx.org/brick/reflection/downloads)](https://packagist.org/packages/brick/reflection)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

## Installation

This library is installable via [Composer](https://getcomposer.org/):

```bash
composer require brick/reflection
```

## Requirements

This library requires PHP 7.2 or later.

## Project status & release process

This library is still under development.

The current releases are numbered `0.x.y`. When a non-breaking change is introduced (adding new methods, optimizing
existing code, etc.), `y` is incremented.

**When a breaking change is introduced, a new `0.x` version cycle is always started.**

It is therefore safe to lock your project to a given release cycle, such as `0.4.*`.

If you need to upgrade to a newer release cycle, check the [release history](https://github.com/brick/reflection/releases)
for a list of changes introduced by each further `0.x.0` version.

# Documentation

Here is a brief overview of the classes in this package.

## ReflectionTools

This class is a collection of tools that build on top of PHP's reflection classes to provide additional functionality.

Just create an instance of `ReflectionTools` and you can use the following methods:

- `getClassMethods()` returns reflections of all the non-static methods that make up one object, including private methods of parent classes.
- `getClassProperties()` returns reflections of all the non-static properties that make up one object, including private properties of parent classes.
- `getClassHierarchy()` returns the hierarchy of classes, starting from the first ancestor and ending with the class itself.
- `getReflectionFunction()` returns a reflection object for any callable.
- `getParameterTypes()` returns the types of a parameter, using native type-hints or falling back to the `@param` types documented.
- `getPropertyTypes()` returns the types of a property, using native type-hints or falling back to the `@var` types documented.
- `getFunctionName()` returns a meaningful name for the given function, including the class name if it is a method.
- `exportFunction()` exports a function's signature.
- `exportFunctionParameters()` exports a function's parameters. `Used by exportFunction()`.

## ImportResolver

ImportResolver resolves class names to their fully qualified name, taking into account the current namespace and `use` statements of the PHP file they were used in.

This is particularly useful for parsing annotations such as `@param ClassName $foo`, where the FQCN of ClassName depends on the file it appears in, for example:

```php
<?php

namespace App;

use Foo\Bar;
use Foo\Bar\Baz as Alias;

class Test {

}
```

You can create a resolver by passing any reflection object belonging to the PHP file as a context: a `ReflectionClass`, a `ReflectionProperty`, a `ReflectionMethod` or a `ReflectionParameter`:

```php
use Brick\Reflection\ImportResolver;

$class = new ReflectionClass(App\Test::class);
$resolver = new ImportResolver($class);

echo $resolver->resolve('\Some\FQCN\Class'); // Some\FQCN\Class
echo $resolver->resolve('Something'); // App\Something
echo $resolver->resolve('Something\Else'); // App\Something\Else
echo $resolver->resolve('Bar'); // Foo\Bar
echo $resolver->resolve('Bar\tender'); // Foo\Bar\tender
echo $resolver->resolve('Alias'); // Foo\Bar\Baz
echo $resolver->resolve('Alias\ooka'); // Foo\Bar\Baz\ooka
```
