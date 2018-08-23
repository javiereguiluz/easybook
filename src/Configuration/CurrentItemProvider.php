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

    /**
     * @param mixed $value
     */
    public function setItemProperty(string $name, $value): void
    {
        $this->item[$name] = $value;
    }

    /**
     * @return null|mixed
     */
    public function getItemProperty(string $name)
    {
        return $this->item[$name] ?? null;
    }
}
