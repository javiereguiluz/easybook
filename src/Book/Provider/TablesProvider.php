<?php declare(strict_types=1);

namespace Easybook\Book\Provider;

final class TablesProvider
{
    /**
     * @var mixed[]
     */
    private $tables = [];

    /**
     * @param mixed[] $table
     */
    public function addTable(array $table): void
    {
        $this->tables[] = $table;
    }

    /**
     * @return mixed[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }
}
