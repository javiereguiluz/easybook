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

use Exception;
use Michelf\Markdown as OriginalMarkdownParser;
use Michelf\MarkdownExtra as ExtraMarkdownParser;

/**
 * This class implements a full-featured Markdown parser.
 */
final class MarkdownParser implements ParserInterface
{
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
                $parser = new OriginalMarkdownParser();
                break;

            case 'php-markdown-extra':
                $parser = new ExtraMarkdownParser();
                break;

            case 'easybook':
                $parser = new EasybookMarkdownParser;

                // replace <!--BREAK--> with {pagebreak} to prevent Markdown
                // parser from considering <!--BREAK--> as a regular HTML comment
                $content = str_replace('<!--BREAK-->', '{pagebreak}', $content);
                break;
        }

        return $parser->transform($content);
    }
}
