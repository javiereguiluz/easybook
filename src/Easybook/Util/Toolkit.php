<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Util;

class Toolkit
{
    /*
     * Merges any number of arrays. The values of the right arrays
     * replace the values of the left arrays. This is very different
     * from PHP built-in array_merge_recursive()
     *
     * @param $replaceNumericKeys if true, all the keys of the arrays will
     *                            be replaced. This is rare for most applications
     *                            but it's the common case for easybook
     *
     * code inspired by:
     * http://www.php.net/manual/en/function.array-merge-recursive.php#104145
     */
    public static function array_deep_merge($replaceNumericKeys = true)
    {
        if (func_num_args() < 2) {
            trigger_error(__FUNCTION__ .' needs two or more array arguments', E_USER_WARNING);
            return;
        }
        
        $arrays = func_get_args();
        $merged = array();
        
        while ($arrays) {
            $array = array_shift($arrays);
            
            if (!is_array($array)) {
                trigger_error(__FUNCTION__ .' encountered a non array argument', E_USER_WARNING);
                return;
            }
            
            if (!$array) {
                continue;
            }
            
            foreach ($array as $key => $value) {
                if (is_string($key) || $replaceNumericKeys) {
                    if (is_array($value)
                        && array_key_exists($key, $merged)
                        && is_array($merged[$key])
                    ) {
                        $merged[$key] = call_user_func(
                            __CLASS__.'::'.__FUNCTION__,
                            $merged[$key],
                            $value
                        );
                    }
                    else {
                        $merged[$key] = $value;
                    }
                }
                else {
                    $merged[] = $value;
                }
            }
        }
        
        return $merged;
    }
    
    /*
     * Zips a complete directory with one call:
     *     zip('/path/to/any/dir', 'compressed.zip');
     *
     * copied from http://stackoverflow.com/a/1334949
     */
    public static function zip($source, $destination)
    {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return false;
        }
    
        $zip = new \ZipArchive();
        if (!$zip->open($destination, \ZIPARCHIVE::CREATE)) {
            return false;
        }
    
        $source = str_replace('\\', '/', realpath($source));
    
        if (is_dir($source)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source),
                \RecursiveIteratorIterator::SELF_FIRST
            );
    
            foreach ($files as $file) {
                $file = str_replace('\\', '/', realpath($file));
    
                if (is_dir($file)) {
                    $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                }
                elseif (is_file($file)) {
                    $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                }
            }
        }
        elseif (is_file($source)) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }
    
        return $zip->close();
    }
    
    /*
     * Generates valid RFC 4211 compliant Universally Unique IDentifiers (UUID) version 4
     *
     * code copied from http://www.php.net/manual/en/function.uniqid.php#94959
     */
    public static function uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Camelizes a string: 'updated_at' -> 'updatedAt'
     *
     * @param string $string     A string to camelize
     * @param bool   $upperFirst If true, the first letter is also uppercased
     *                           'updated_at' -> 'UpdatedAt'
     *
     * @return string The camelized string
     *
     * code adapted from Symfony\Component\DependencyInjection\Container.php
     */
    static public function camelize($string, $upperFirst = false)
    {
        return preg_replace_callback('/(^|_|\.)+(.)/', function ($match) use ($upperFirst) {
            $camelized = ('.' === $match[1] ? '_' : '').strtoupper($match[2]);
            return $upperFirst ? ucfirst($camelized) : $camelized;
        }, $string);
    }
}