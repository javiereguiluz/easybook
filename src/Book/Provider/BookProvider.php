<?php declare(strict_types=1);

namespace Easybook\Book\Provider;

use Easybook\Book\Book;
use Easybook\Book\Factory\BookFactory;
use Easybook\Exception\Configuration\MissingParameterException;
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

        $bookParameter = $this->parameterProvider->provideParameter('book');
        if (! is_array($bookParameter)) {
            throw new MissingParameterException(sprintf(
                'Parameter "%s" is missing, have you add it under "parameters > %s" section?',
                'book',
                'book'
            ));
        }

        $this->book = $this->bookFactory->createFromBookParameter($this->parameterProvider->provideParameter('book'));

        return $this->book;
    }
}
