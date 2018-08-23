<?php declare(strict_types=1);

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
    private $bookEdition;

    public function __construct(string $bookEdition)
    {
        $this->bookEdition = $bookEdition;
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
            $this->bookEdition
        ));
    }

    /**
     * @return PublisherInterface[]
     */
    public function getPublishers(): array
    {
        return $this->publishers;
    }
}
