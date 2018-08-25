<?php declare(strict_types=1);

namespace Easybook\Book\Provider;

use Easybook\Book\Edition;

final class EditionProvider
{
    /**
     * @var Edition
     */
    private $edition;

    public function setEdition(Edition $edition): void
    {
        $this->edition = $edition;
    }

    public function provide(): Edition
    {
        return $this->edition;
    }
}
