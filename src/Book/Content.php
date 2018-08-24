<?php declare(strict_types=1);

namespace Easybook\Book;

final class Content
{
    /**
     * @var string
     */
    private $element;

    public function getElement(): string
    {
        return $this->element;
    }

    public function setElement(string $element): void
    {
        $this->element = $element;
    }
}
