<?php declare(strict_types=1);

namespace Easybook\Book;

final class Book
{
    /**
     * @var Content[]
     */
    private $contents = [];

    /**
     * @return Content[]
     */
    public function getContents(): array
    {
        return $this->contents;
    }

    public function removeContent(Content $content): void
    {
        foreach ($this->contents as $key => $content) {
            if ($content === $content) {
                unset($this->contents[$key]);
                break;
            }
        }
    }
}
