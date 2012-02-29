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

class Slugger
{
    private $app;
    private $separator;
    private $prefix;
    private $unique;
    
    public function __construct($app, $separator = '-', $prefix = '', $unique = true)
    {
        $this->app = $app;
        $this->separator = $separator;
        $this->prefix    = $prefix;
        $this->unique    = $unique;
    }
    
    // adapted from http://cubiq.org/the-perfect-php-clean-url-generator
    public function slugify($string)
    {
        $string = strip_tags($string);

        if (function_exists('iconv')) {
            $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        }
        else if (function_exists('mb_convert_encoding')) {
            $slug = mb_convert_encoding($string, 'ASCII');
        }
        else {
            // if both iconv and mb_* functions are unavailable, use a
            // simple method to remove accents
            // TODO: Is it better to just throw an exception?
            $slug = strtr(
                utf8_decode($string), 
                utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
                'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy'
            );
        }

        $slug = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $slug);
        $slug = strtolower(trim($slug, $this->separator));
        $slug = preg_replace("/[\/_|+ -]+/", $this->separator, $slug);

        $slug = $this->prefix.$slug;

        // $slugs array must hold original slugs, without unique substring
        $slugs = $this->app->append('publishing.slugs', $slug);

        if ($this->unique) {
            $occurrences = array_count_values($slugs);

            $count = $occurrences[$slug];
            if ($count > 1) {
                $slug .= $this->separator.(++$count);
            }
        }

        return $slug;
    }
}