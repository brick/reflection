# Changelog

## UNRELEASED (0.5.0)

💥 **Breaking changes**

- Minimum PHP version is now `8.0`
- The following methods have been **removed**:
  - `ReflectionTools::getParameterTypes()`
  - `ReflectionTools::getPropertyTypes()`

🐛 **Bug fixes**

- `ReflectionTools::exportFunction()`: constants are now properly exported with a leading `\`
- `ReflectionTools::exportFunction()`: nullable types are now always output with a leading `?`

## [0.4.1](https://github.com/brick/reflection/releases/tag/0.4.1) - 2020-10-24

🐛 **Bug fix**

- `ReflectionTools::exportFunction()` returned a `?`-nullable type for untyped parameters (#2)

## [0.4.0](https://github.com/brick/reflection/releases/tag/0.4.0) - 2020-09-28

✨ **New features**

- **PHP 8 compatibility** 🚀
- `ReflectionTools::getPropertyTypes()` now supports PHP 8 union types
- `ReflectionTools::getParameterTypes()` now supports reflection & PHP 8 union types

💥 **Breaking changes**

- `ReflectionTools::getParameterTypes()` now reads types from reflection first
- `ReflectionTools::getPropertyTypes()` and `getParameterTypes()`:
    - always return class names as FQCN (including namespace)
    - always return built-in types as lowercase
- `ReflectionTools::getFunctionParameterTypes()` has been removed
- `ReflectionTools::getPropertyClass()` has been removed

⬆️ **Dependency upgrade**

- For compatibility with PHP 8, this version requires `doctrine/annotations: ^1.10.4`

## [0.3.0](https://github.com/brick/reflection/releases/tag/0.3.0) - 2019-12-24

Minimum PHP version is now `7.2`. No other changes.

## [0.2.4](https://github.com/brick/reflection/releases/tag/0.2.4) - 2019-11-05

Fix support for typed properties in `ReflectionTools::getPropertyClass()`.

## [0.2.3](https://github.com/brick/reflection/releases/tag/0.2.3) - 2019-11-05

Support for typed properties (PHP 7.4) in `ReflectionTools::getPropertyTypes()`.

## [0.2.2](https://github.com/brick/reflection/releases/tag/0.2.2) - 2019-03-27

**Improvement**

`ReflectionTools::getClassMethods()` and `getClassProperties()` now always respect the hierarchical order, returning methods and properties from parent classes first.

## [0.2.1](https://github.com/brick/reflection/releases/tag/0.2.1) - 2017-10-13

**Internal refactoring.** Several methods now have a simpler implementation.

## [0.2.0](https://github.com/brick/reflection/releases/tag/0.2.0) - 2017-10-03

**Minimum PHP version is now 7.1.**

`ReflectionTools::exportFunction()` now supports scalar type hints, return types, nullable types and variadics.

## [0.1.0](https://github.com/brick/reflection/releases/tag/0.1.0) - 2017-10-03

First beta release.

