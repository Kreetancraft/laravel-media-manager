<?php

namespace Kreetancraft\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

/**
 * A stable, comparable description of a package's public API.
 *
 * Deliberately narrow: public classes and their public methods, with parameter
 * and return types. Internals change freely — the promise is only about what a
 * consumer can reach. Mark anything else `@internal` to keep it out.
 */
final class ApiSurface
{
    /**
     * @return array<string, string> symbol => signature
     */
    public static function for(string $srcDir, string $namespace): array
    {
        $surface = [];

        foreach (self::classesIn($srcDir, $namespace) as $class) {
            $reflection = new ReflectionClass($class);

            if (self::isInternal($reflection->getDocComment())) {
                continue;
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                // Inherited methods belong to whoever declared them.
                if ($method->getDeclaringClass()->getName() !== $class) {
                    continue;
                }

                if (self::isInternal($method->getDocComment())) {
                    continue;
                }

                $surface[$class.'::'.$method->getName()] = self::signature($method);
            }
        }

        ksort($surface);

        return $surface;
    }

    private static function signature(ReflectionMethod $method): string
    {
        $params = [];

        foreach ($method->getParameters() as $parameter) {
            $type = self::typeName($parameter->getType());
            $optional = $parameter->isDefaultValueAvailable() ? '?' : '';
            $variadic = $parameter->isVariadic() ? '...' : '';
            $params[] = trim($type.' '.$variadic.'$'.$parameter->getName().$optional);
        }

        return '('.implode(', ', $params).'): '.self::typeName($method->getReturnType());
    }

    private static function typeName(mixed $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            $nullable = $type->allowsNull() && $type->getName() !== 'mixed' ? '?' : '';

            return $nullable.$type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(
                static fn ($t) => $t instanceof ReflectionNamedType ? $t->getName() : (string) $t,
                $type->getTypes()
            ));
        }

        return 'mixed';
    }

    private static function isInternal(string|false $docblock): bool
    {
        return is_string($docblock) && str_contains($docblock, '@internal');
    }

    /**
     * @return list<class-string>
     */
    private static function classesIn(string $dir, string $namespace): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $separator = chr(92);
        $classes = [];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($dir) + 1);
            $relative = str_replace([$separator, '/'], $separator, $relative);

            $class = $namespace.$separator.substr($relative, 0, -4);

            // Skip helper files and anything without a matching declaration.
            if (! class_exists($class) && ! interface_exists($class) && ! trait_exists($class)) {
                continue;
            }

            $classes[] = $class;
        }

        sort($classes);

        return $classes;
    }
}
