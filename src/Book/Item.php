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
     * Required by epub2
     *
     * @var string
     */
    private $pageName;

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
        $this->content = $original;

        $this->label = $label;
        $this->setTitle($title);
        $this->tableOfContents = $tableOfContents;
    }

    public static function createFromConfigNumberAndContent(int $itemNumber, string $content): self
    {
        $itemConfig = new ItemConfig('', '', '', $itemNumber, '');

        return new self($itemConfig, $content, '', '', []);
    }

    /**
     * @todo use "page_name"
     */
    public function setPageName(string $pageName): void
    {
        $this->pageName = $pageName;
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

    public function changeLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getConfigNumber(): ?int
    {
        return $this->itemConfig->getNumber();
    }

    public function getConfigElement(): string
    {
        return $this->itemConfig->getElement();
    }

    public function getPageName(): ?string
    {
        return $this->pageName;
    }

    private function setTitle(string $title): void
    {
        $this->slug = Strings::webalize($title);
    }
}
