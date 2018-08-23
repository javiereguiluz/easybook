<?php declare(strict_types=1);

namespace Easybook\Parsers;

use Michelf\MarkdownExtra;

/**
 * This class implements a full-featured Markdown parser.
 */
final class MarkdownParser implements ParserInterface
{
    /**
     * @var MarkdownExtra
     */
    private $markdownExtra;


    public function __construct(MarkdownExtra $markdownExtra)
    {
        $this->markdownExtra = $markdownExtra;
    }

    /**
     * Transforms the original Markdown content into the desired output format.
     *
     * @param string $content      The original content to be parsed
     *
     * @throws \Exception
     * @return string The parsed content
     */
    public function transform(string $content): string
    {
        return $this->markdownExtra->transform($content);
    }
}
