<?php declare(strict_types=1);

namespace Easybook\Parsers;

use Michelf\MarkdownExtra;

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
     * Transforms Markdown content into HTML
     */
    public function transform(string $content): string
    {
        return $this->markdownExtra->transform($content);
    }
}
