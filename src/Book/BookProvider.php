<?php declare(strict_types=1);

namespace Easybook\Book;

final class BookProvider
{
    /**
     * @var Book
     */
    private $book;

    public function setBook(Book $book): void
    {
        $this->book = $book;
    }

    public function provide(): Book
    {
        return $this->book;
    }
}
