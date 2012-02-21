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

use Easybook\Parsers\Markdown\MarkdownExtraParser;

class MdParser extends BaseParser
{
    public function parse($content)
    {
        $outputFormat = $this->app->edition('format');
        
        switch ($outputFormat) {
            case 'pdf':
            case 'html':
            case 'html_chunked':
            case 'epub':
            case 'epub2':
            case 'epub3':
                return $this->parseToHtml($content);

            default:
                throw new \Exception(sprintf(
                    'No markdown parser available for "%s" format',
                    $outputFormat
                ));
        }
    }
    
    public function parseToHtml($content)
    {
        $parser = new MarkdownExtraParser(array(), $this->app);
        return $parser->transformMarkdown($content);
    }
}
