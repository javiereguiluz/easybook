<?php declare(strict_types=1);

namespace Easybook\Book;

final class ItemConfig
{
    /**
     * The name of this item contents file (it's a relative path from book's `Contents/`)
     *
     * @var string
     */
    private $content;

    /**
     * The type of this content (`chapter`, `appendix`, `toc`, `license`, ...)
     *
     * @var string
     */
    private $element;

    /**
     * The number/letter of the content (useful for `chapter`, `part` and `appendix`)
     *
     * @var int|null
     */
    private $number;

    /**
     * The title of the content defined in `config.yml` (usually only `part` defines it)
     *
     * @var string|null
     */
    private $title;

    public function __construct(string $content, string $element, ?int $number, ?string $title)
    {
        $this->content = $content;
        $this->element = $element;
        $this->number = $number;
        $this->title = $title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getElement(): string
    {
        return $this->element;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
}
