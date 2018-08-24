<?php declare(strict_types=1);

namespace Easybook\Book;

use Nette\Utils\Strings;

final class Item
{
    /**
     * @var ItemConfig
     */
    private $itemConfig;

    /**
     * Original content as written by book author (Markdown
     *
     * @var string
     */
    private $original;

    /**
     * Transformed content of the item (HTML usually)
     *
     * @var string
     */
    private $content;

    /**
     * The label of this item ('Chapter XX', 'Appendix XX', ...)
     *
     * @var string
     */
    private $label;

    /**
     * The title of the item without any label ('Lorem ipsum dolor')
     *
     * @var string
     */
    private $title;

    /**
     * The slug of the title
     *
     * @var string
     */
    private $slug;

    /**
     * The table of contents of this item
     *
     * @var mixed[]
     */
    private $tableOfContents = [];

    /**
     * @param mixed[] $tableOfContents
     */
    public function __construct(
        ItemConfig $itemConfig,
        string $original,
        string $label,
        string $title,
        array $tableOfContents
    ) {
        $this->itemConfig = $itemConfig;
        $this->original = $original;
        $this->label = $label;
        $this->setTitle($title);
        $this->tableOfContents = $tableOfContents;
    }

    public function getItemConfig(): ItemConfig
    {
        return $this->itemConfig;
    }

    public function getOriginal(): string
    {
        return $this->original;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function changeContent(string $content): void
    {
        $this->content = $content;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @todo value object?
     *
     * @return mixed[]
     */
    public function getTableOfContents(): array
    {
        return $this->tableOfContents;
    }

    // wtf, are you used - maybe content?
    public function changeOriginal(string $original): void
    {
        $this->original = $original;
    }

    private function setTitle(string $title): void
    {
        $this->slug = Strings::webalize($title);
    }
}
