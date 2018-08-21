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

use Easybook\Exception\Publisher\FormatPublisherNotSupportedException;

final class PublisherProvider
{
    /**
     * @var PublisherInterface[]
     */
    private $publishers = [];

    /**
     * @var string
     */
    private $publishingEdition;

    public function __construct(string $publishingEdition)
    {
        $this->publishingEdition = $publishingEdition;
    }

    public function addPublisher(PublisherInterface $publisher): void
    {
        $this->publishers[] = $publisher;
    }

    public function provideByFormat(string $format): PublisherInterface
    {
        foreach ($this->publishers as $publisher) {
            if (strtolower($publisher->getFormat()) === strtolower($format)) {
                return $publisher;
            }
        }

        $supportedFormats = array_map(function (PublisherInterface $publisher) {
           return $publisher->getFormat();
        }, $this->publishers);

        throw new FormatPublisherNotSupportedException(sprintf(
            'Unknown "%s" format for "%s" edition. Try one of "%s".',
            implode('", "', $supportedFormats),
            $this->publishingEdition
        ));
    }
}
