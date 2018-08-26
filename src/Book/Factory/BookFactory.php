<?php declare(strict_types=1);

namespace Easybook\Book\Factory;

use Easybook\Book\Book;
use Easybook\Guard\BookGuard;

final class BookFactory
{
    /**
     * @var EditionFactory
     */
    private $editionFactory;

    /**
     * @var BookGuard
     */
    private $bookGuard;

    public function __construct(EditionFactory $editionFactory, BookGuard $bookGuard)
    {
        $this->editionFactory = $editionFactory;
        $this->bookGuard = $bookGuard;
    }

    /**
     * @param mixed[] $bookParameter
     */
    public function createFromBookParameter(array $bookParameter): Book
    {
        $this->bookGuard->ensureBookParameterHasKey($bookParameter, 'name');

        // @todo required items 'name'
        $book = new Book($bookParameter['name']);

        if (isset($bookParameter['editions'])) {
            foreach ((array) $bookParameter['editions'] as $name => $editionParameter) {
                $edition = $this->editionFactory->createFromEditionParameter($editionParameter, $name);
                $book->addEdition($edition);
            }
        }

        return $book;
    }
}
