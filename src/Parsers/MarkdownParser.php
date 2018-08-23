<?php declare(strict_types=1);

namespace Easybook\Parsers;

use Exception;
use Michelf\Markdown;
use Michelf\MarkdownExtra;

/**
 * This class implements a full-featured Markdown parser.
 */
final class MarkdownParser implements ParserInterface
{
    /**
     * @var Markdown
     */
    private $markdown;

    /**
     * @var MarkdownExtra
     */
    private $markdownExtra;

    /**
     * @var EasybookMarkdownParser
     */
    private $easybookMarkdownParser;

    public function __construct(
        Markdown $markdown,
        MarkdownExtra $markdownExtra,
        EasybookMarkdownParser $easybookMarkdownParser
    ) {
        $this->markdown = $markdown;
        $this->markdownExtra = $markdownExtra;
        $this->easybookMarkdownParser = $easybookMarkdownParser;
    }

    /**
     * Transforms the original Markdown content into the desired output format.
     *
     * @param string $content      The original content to be parsed
     * @param string $outputFormat The desired output format (it only supports 'html' for now)
     *
     * @throws \Exception
     *
     * @return string The parsed content
     */
    public function transform(string $content, string $outputFormat = 'html'): string
    {
        $supportedFormats = ['epub', 'epub2', 'epub3', 'html', 'html_chunked', 'pdf'];

        if (! in_array($outputFormat, $supportedFormats, true)) {
            throw new Exception(sprintf('No markdown parser available for "%s" format', $outputFormat));
        }

        $syntax = $this->app['parser.options']['markdown_syntax'];

        return $this->transformToHtml($content, $syntax);
    }

    /**
     * Transforms Markdown input to HTML output.
     *
     * @param string $content The original content to be parsed
     * @param string $syntax  The Markdown syntax to use (original, PHP Extra, easybook, ...)
     *
     * @return string The parsed HTML output
     *
     * @throws \Exception If the given $syntax is not supported
     */
    private function transformToHtml(string $content, string $syntax): string
    {
        $supportedSyntaxes = ['original', 'php-markdown-extra', 'easybook'];

        if (! in_array($syntax, $supportedSyntaxes, true)) {
            throw new Exception(sprintf(
                'Unknown "%s" Markdown syntax (options available: %s)',
                $syntax,
                implode(', ', $supportedSyntaxes)
            ));
        }

        switch ($syntax) {
            case 'original':
                $parser = $this->markdown;
                return $parser->transform($content);

            case 'php-markdown-extra':
                $parser = $this->markdownExtra;
                return $parser->transform($content);

            case 'easybook':
                $parser = $this->easybookMarkdownParser;

                // replace <!--BREAK--> with {pagebreak} to prevent Markdown
                // parser from considering <!--BREAK--> as a regular HTML comment
                $content = str_replace('<!--BREAK-->', '{pagebreak}', $content);
                return $parser->transform($content);
        }
    }
}