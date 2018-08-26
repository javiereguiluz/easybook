<?php declare(strict_types=1);

namespace Easybook\Publisher;

interface PublisherInterface
{
    public function getFormat(): string;

    /**
     * It defines the complete workflow followed to publish a book (load its
     * contents, transform them into HTML files, etc.).
     */
    public function publishBook(): void;

    public function decorateContents(): void;
}
