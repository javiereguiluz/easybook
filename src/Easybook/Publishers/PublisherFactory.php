<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Publishers;

final class PublisherFactory
{
    public function createByFormat(string $format): PublisherInterface
    {
        switch (strtolower($outputFormat)) {
            case 'pdf':
                $publisher = new PdfPublisher($app);
                break;

            case 'html':
                $publisher = new HtmlPublisher($app);
                break;

            case 'html_chunked':
                $publisher = new HtmlChunkedPublisher($app);
                break;

            case 'epub':
                $publisher = new Epub2Publisher($app);
                break;

            default:
                throw new \RuntimeException(sprintf(
                    'Unknown "%s" format for "%s" edition (allowed: "pdf", "html", "html_chunked", "epub", "mobi")',
                    $outputFormat,
                    $app['publishing.edition']
                ));
            }

            return $publisher;
        };
    }
}
