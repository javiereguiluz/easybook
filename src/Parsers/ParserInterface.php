<?php declare(strict_types=1);

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
