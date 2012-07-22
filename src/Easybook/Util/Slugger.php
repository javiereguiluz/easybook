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
    private $options;

    public function __construct($app)
    {
        $this->app = $app;
    }

    // adapted from http://cubiq.org/the-perfect-php-clean-url-generator
    public function slugify($string, $options = array())
    {
        $this->options = array_merge(array(
            'separator' => '-',  // used between words and instead of illegal characters
            'prefix'    => '',   // prefix to be appended at the beginning of the slug
            'unique'    => true, // should this slug be unique across the entire book?
        ), $options);

        $string = strip_tags($string);

        if (function_exists('iconv')) {
            $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        } elseif (function_exists('mb_convert_encoding')) {
            $slug = mb_convert_encoding($string, 'ASCII');
        } else {
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
        $slug = strtolower(trim($slug, $this->options['separator']));
        $slug = preg_replace("/[\/_|+ -]+/", $this->options['separator'], $slug);

        $slug = $this->options['prefix'].$slug;

        // $slugs array must hold original slugs, without unique substring
        $slugs = $this->app->append('publishing.slugs', $slug);

        if ($this->options['unique']) {
            $occurrences = array_count_values($slugs);

            $count = $occurrences[$slug];
            if ($count > 1) {
                $slug .= $this->options['separator'].(++$count);
            }
        }

        return $slug;
    }
}
