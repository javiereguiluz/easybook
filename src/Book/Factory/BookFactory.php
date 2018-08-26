<?php declare(strict_types=1);

namespace Easybook\Book\Factory;

use Easybook\Book\Book;

final class BookFactory
{
    public function createFromBookParameter(array $book): Book
    {
        dump($book);
        die;
    }
}
