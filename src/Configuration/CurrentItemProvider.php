<?php declare(strict_types=1);

namespace Easybook\Configuration;

final class CurrentItemProvider
{
    /**
     * @var mixed[]
     */
    private $item = [];

    /**
     * @param mixed[] $item
     */
    public function setItem(array $item): void
    {
        $this->item = $item;
    }

    /**
     * @return mixed[]
     */
    public function getItem(): array
    {
        return $this->item;
    }
}
