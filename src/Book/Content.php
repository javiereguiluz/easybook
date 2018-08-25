<?php declare(strict_types=1);

namespace Easybook\Book;

final class Content
{
    /**
     * @var string
     */
    private $element;

    /**
     * @var int|null
     */
    private $number;

    /**
     * @var int|string
     */
    private $content;

    public function getElement(): string
    {
        return $this->element;
    }

    public function setElement(string $element): void
    {
        $this->element = $element;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }
}
