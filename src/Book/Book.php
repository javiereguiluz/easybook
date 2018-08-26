<?php declare(strict_types=1);

namespace Easybook\Book;

final class Book
{
    /**
     * @var Content[]
     */
    private $contents = [];

    /**
     * @var Edition[]
     */
    private $editions = [];

    /**
     * @return Content[]
     */
    public function getContents(): array
    {
        return $this->contents;
    }

    public function removeContent(Content $toBeRemovedContent): void
    {
        foreach ($this->contents as $key => $content) {
            if ($content === $toBeRemovedContent) {
                unset($this->contents[$key]);
                break;
            }
        }
    }

    public function addEdition(Edition $edition): void
    {
        $this->editions[] = $edition;
    }

    /**
     * @return Edition[]
     */
    public function getEditions(): array
    {
        return $this->editions;
    }
}
