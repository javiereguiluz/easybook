<?php declare(strict_types=1);

namespace Easybook\Book\Factory;

use Easybook\Book\Book;

final class BookFactory
{
    /**
     * @var EditionFactory
     */
    private $editionFactory;

    public function __construct(EditionFactory $editionFactory)
    {
        $this->editionFactory = $editionFactory;
    }

    /**
     * @param mixed[] $bookParameter
     */
    public function createFromBookParameter(array $bookParameter): Book
    {
        // @todo required items 'name'
        $book = new Book($bookParameter['name']);

        if (isset($bookParameter['editions'])) {
            foreach ((array) $bookParameter['editions'] as $editionParameter) {
                $edition = $this->editionFactory->createFromEditionParameter($editionParameter);
                $book->addEdition($edition);
            }
        }

        return $book;
    }
}
