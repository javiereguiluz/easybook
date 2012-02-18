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
}