<?php declare(strict_types=1);

namespace Easybook\Publisher;

/**
 * @todo
 */
final class PdfPublisher extends AbstractPublisher
{
    /**
     * @var string
     */
    public const NAME = 'pdf';

    public function getFormat(): string
    {
        return self::NAME;
    }

    /**
     * @todo Make part of internal API
     *
     * Decorates each book item with the appropriate Twig template.
     */
    public function decorateContents(): void
    {
        foreach ($this->publishingItems as $item) {
            $item->changeContent($this->renderer->render($item->getConfigElement() . '.twig', [
                'item' => $item,
            ]));
        }
    }

    protected function assembleBook(string $outputDirectory): string
    {
        return $outputDirectory . '/book.pdf';
    }
}
