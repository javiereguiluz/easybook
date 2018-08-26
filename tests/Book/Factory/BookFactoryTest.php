<?php declare(strict_types=1);

namespace Easybook\Tests\Book\Factory;

use Easybook\Book\Book;
use Easybook\Book\Factory\BookFactory;
use Easybook\Tests\AbstractCustomConfigContainerAwareTestCase;

final class BookFactoryTest extends AbstractCustomConfigContainerAwareTestCase
{
    /**
     * @var BookFactory
     */
    private $bookFactory;

    /**
     * @var mixed[]
     */
    private $bookParameter = [];

    protected function setUp(): void
    {
        $this->bookFactory = $this->container->get(BookFactory::class);
        $this->bookParameter = $this->container->getParameter('book');
    }

    public function test(): void
    {
        $book = $this->bookFactory->createFromBookParameter($this->bookParameter);
        $this->assertInstanceOf(Book::class, $book);

        $this->assertCount(1, $book->getEditions());

        $edition = $book->getEditions()[0];
        $this->assertCount(1, $edition->getBeforePublishScripts());
        $this->assertCount(0, $edition->getAfterPublishScripts());
    }

    protected function provideConfig(): string
    {
        return __DIR__ . '/config/config-book.yml';
    }
}
