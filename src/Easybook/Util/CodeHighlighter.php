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

class CodeHighlighter
{
    private $app;

    /**
     * @param array app The object that represents the whole dependency container
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Highlights the given code according to the specified programming language.
     *
     * @param  string $code     The source code to be highlighted
     * @param  string $language The name of the programming language used in the code
     *
     * @return string           The highlighted code
     *
     * @throws \RuntimeException If the cache used to store the highlighted code isn't writable
     */
    public function highlight($code, $language)
    {
        if ('html' == $language) { $language = 'html5'; }

        // check if the code exists in the cache
        if ($this->app->edition('highlight_cache')) {
            // inspired by Twig_Environment -> getCacheFileName()
            // see https://github.com/fabpot/Twig/blob/master/lib/Twig/Environment.php
            $hash = md5($language.$code);
            $cacheDir = $this->app['app.dir.cache'].'/CodeHighlighter/'.substr($hash, 0, 2).'/'.substr($hash, 2, 2);
            $cacheFilename = $cacheDir.'/'.substr($hash, 4).'.txt';

            if (file_exists($cacheFilename)) {
                return file_get_contents($cacheFilename);
            }
        }

        // highlight the code using the best available highlighting library
        // (for now, easybook is limited to always using the GeSHi library)
        $geshi = $this->app['geshi'];
        $geshi->set_source($code);
        $geshi->set_language($language);
        $highlightedCode = $geshi->parse_code();

        // save the highlighted code in the cache
        if ($this->app->edition('highlight_cache')) {
            $this->app['filesystem']->mkdir($cacheDir);

            if (false === @file_put_contents($cacheFilename, $highlightedCode)) {
                throw new \RuntimeException(sprintf("ERROR: Failed to write cache file \n'%s'.", $cacheFilename));
            }
        }

        return $highlightedCode;
    }
}