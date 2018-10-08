<?php declare(strict_types=1);

namespace Easybook\Contract\Publisher;

interface PublisherInterface
{
    public function getFormat(): string;

    /**
     * It defines the complete workflow followed to publish a book (load its
     * contents, transform them into HTML files, etc.).
     *
     * @return string Path to published file.
     */
    public function publishBook(string $outputDirectory): string;

    // @todo interfac - create book from edition to output.
}
