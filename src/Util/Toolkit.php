<?php declare(strict_types=1);

namespace Easybook\Util;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symplify\PackageBuilder\Yaml\ParametersMerger;
use ZipArchive;

final class Toolkit
{
    /**
     * Merges any number of arrays. The values of the right arrays
     * replace the values of the left arrays. This is very different
     * from PHP built-in array_merge_recursive().
     *
     * All the keys of the arrays will be replaced. This is rare for most
     * applications but it's the common case for easybook.
     *
     * code inspired by:
     * http://www.php.net/manual/en/function.array-merge-recursive.php#104145
     *
     * @return mixed[]
     */
    public function arrayDeepMergeAndReplace(...$arrays): array
    {
        $merged = [];

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                    $merged[$key] = self::arrayDeepMergeAndReplace($merged[$key], $value);
                } else {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }

    /**
     * Zips recursively a complete directory with one call (using PHP ZIP extension):
     *     zip('/path/to/any/dir', 'compressed.zip');
     *
     * Code copied from http://stackoverflow.com/a/1334949
     *
     * @param  string $source       The directory with the files to compress
     * @param  string $destination  The path of the generated ZIP file
     */
    public function zip(string $source, string $destination)
    {
        if (! file_exists($source)) {
            return false;
        }

        $zip = new ZipArchive();
        if (! $zip->open($destination, ZIPARCHIVE::CREATE)) {
            return false;
        }

        $source = str_replace('\\', '/', realpath($source));
        $parent = pathinfo($source, PATHINFO_DIRNAME);

        if (is_dir($source)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($files as $file) {
                $file = str_replace('\\', '/', realpath($file));

                if (is_dir($file)) {
                    if ($file !== $parent) {
                        $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                    }
                } elseif (is_file($file)) {
                    $filename = str_replace($source . '/', '', $file);
                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));

                    $zip->setCompressionName($filename, ZipArchive::CM_STORE);
                    // due too \Easybook\Publishers\Epub2Publisher::zipBookContents()
                    // see https://github.com/php/php-src/commit/3a55ea02
                    // $zip->setCompressionIndex(2, ZipArchive::CM_STORE);
                }
            }
        } elseif (is_file($source)) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }

        return $zip->close();
    }

    public function unzip(string $file, string $destination): bool
    {
        $zip = new ZipArchive();
        $file = str_replace('\\', '/', realpath($file));

        if (! $zip->open($file)) {
            return false;
        }

        if (! $zip->extractTo($destination)) {
            return false;
        }

        return $zip->close();
    }

    /**
     * Camelizes a string: 'updated_at' -> 'updatedAt'.
     *
     * @param string $string     A string to camelize
     * @param bool   $upperFirst If true, the first letter is also uppercased
     *                           'updated_at' -> 'UpdatedAt'
     *
     * @return string The camelized string
     *
     * code adapted from Symfony\Component\DependencyInjection\Container.php
     */
    public static function camelize(string $string, bool $upperFirst = false): string
    {
        return preg_replace_callback('/(^|_|\.)+(.)/', function ($match) use ($upperFirst) {
            $camelized = ($match[1] === '.' ? '_' : '') . strtoupper($match[2]);

            return $upperFirst ? ucfirst($camelized) : $camelized;
        }, $string);
    }
}
