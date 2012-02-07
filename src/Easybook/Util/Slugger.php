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
    private $container;
    private $separator;
    private $prefix;
    private $unique;
    
    public function __construct($container, $separator = '-', $prefix = '', $unique = true)
    {
        $this->container = $container;
        $this->separator = $separator;
        $this->prefix    = $prefix;
        $this->unique    = $unique;
    }
    
    public function slugify($string)
    {
        // Copied from http://cubiq.org/the-perfect-php-clean-url-generator
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        $slug = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $slug);
        $slug = strtolower(trim($slug, $this->separator));
        $slug = preg_replace("/[\/_|+ -]+/", $this->separator, $slug);
        
        $slug = $this->prefix.$slug;
        
        // $slugs array must hold original slugs, without unique substring
        $slugs = $this->container->append('publishing.slugs', $slug);

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