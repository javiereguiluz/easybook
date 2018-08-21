<?php declare(strict_types=1);

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Parsers;

/**
 * Interface implemented by content parser classes.
 */
interface ParserInterface
{
    /**
     * Converts the original content (e.g. Markdown) into the appropriate
     * content for publishing (e.g. HTML).
     *
     * @param string $content The original content to be parsed
     *
     * @return string The parsed content
     */
    public function transform(string $content): string;
}
