<?php declare(strict_types=1);

namespace Easybook\Book\Provider;

use Easybook\Book\Book;
use Easybook\Book\Factory\BookFactory;
use Symplify\PackageBuilder\Parameter\ParameterProvider;

final class BookProvider
{
    /**
     * @var Book
     */
    private $book;

    /**
     * @var BookFactory
     */
    private $bookFactory;

    /**
     * @var ParameterProvider
     */
    private $parameterProvider;

    public function __construct(BookFactory $bookFactory, ParameterProvider $parameterProvider)
    {
        $this->bookFactory = $bookFactory;
        $this->parameterProvider = $parameterProvider;
    }

    public function provide(): Book
    {
        if ($this->book) {
            return $this->book;
        }

        $this->book = $this->bookFactory->createFromBookParameter($this->parameterProvider->provideParameter('book'));

        return $this->book;
    }
}
