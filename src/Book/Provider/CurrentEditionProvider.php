<?php declare(strict_types=1);

namespace Easybook\Book\Provider;

final class CurrentEditionProvider
{
    /**
     * @var string|null
     */
    private $edition;

    public function setEdition(string $edition): void
    {
        $this->edition = ucfirst($edition);
    }

    public function provide(): ?string
    {
        return $this->edition;
    }
}