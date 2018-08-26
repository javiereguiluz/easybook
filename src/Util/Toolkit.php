<?php declare(strict_types=1);

namespace Easybook\Util;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

final class Toolkit
{
    /**
     * Zips recursively a complete directory with one call (using PHP ZIP extension):
     *     zip('/path/to/any/dir', 'compressed.zip');
     *
     * Code copied from http://stackoverflow.com/a/1334949
     */
    public function zip(string $source, string $destination): bool
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
}
