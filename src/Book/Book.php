<?php declare(strict_types=1);

namespace Easybook\Book;

use Nette\Utils\Strings;

final class Book
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var Content[]
     */
    private $contents = [];

    /**
     * @var Edition[]
     */
    private $editions = [];

    /**
     * @var string
     */
    private $slug;

    public function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * @return Content[]
     */
    public function getContents(): array
    {
        return $this->contents;
    }

    public function removeContent(Content $toBeRemovedContent): void
    {
        foreach ($this->contents as $key => $content) {
            if ($content === $toBeRemovedContent) {
                unset($this->contents[$key]);
                break;
            }
        }
    }

    public function addEdition(Edition $edition): void
    {
        $this->editions[] = $edition;
    }

    /**
     * @return Edition[]
     */
    public function getEditions(): array
    {
        return $this->editions;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function setName(string $name): void
    {
        $this->name = $name;
        $this->slug = Strings::webalize($name);
    }
}
