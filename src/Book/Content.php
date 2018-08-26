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
     * @var string|null
     */
    private $content;

    /**
     * @var string|null
     */
    private $title;

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

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
