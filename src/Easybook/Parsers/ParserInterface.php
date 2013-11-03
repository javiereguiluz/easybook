<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Parsers;

use Easybook\DependencyInjection\Application;

/**
 * Interface implemented by content parser classes.
 */
interface ParserInterface
{
    /**
     * Class constructor. It receives an Application instance to get access
     * to important elements such as the slugger and the characteristics of
     * the book being parsed.
     *
     * @param Application $app The easybook application being executed
     */
    public function __construct(Application $app);

    /**
     * Converts the original content (e.g. Markdown) into the appropriate
     * content for publishing (e.g. HTML)
     *
     * @param string $content The original content to be parsed
     * 
     * @return string The parsed content
     */
    public function transform($content);
}
