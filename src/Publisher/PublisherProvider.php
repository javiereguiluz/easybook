<?php declare(strict_types=1);

namespace Easybook\Publisher;

use Easybook\Exception\Publisher\FormatPublisherNotSupportedException;

final class PublisherProvider
{
    /**
     * @var PublisherInterface[]
     */
    private $publishers = [];

    public function addPublisher(PublisherInterface $publisher): void
    {
        $this->publishers[strtolower($publisher->getFormat())] = $publisher;
    }

    public function provideByFormat(string $format): PublisherInterface
    {
        $format = strtolower($format);
        if (isset($this->publishers[$format])) {
            return $this->publishers[$format];
        }

        throw new FormatPublisherNotSupportedException(sprintf(
            'Unknown "%s" format. Try one of: "%s".',
            $format,
            implode('", "', array_keys($this->publishers))
        ));
    }
}
