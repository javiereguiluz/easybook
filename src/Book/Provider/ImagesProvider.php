<?php declare(strict_types=1);

namespace Easybook\Book\Provider;

final class ImagesProvider
{
    /**
     * @var mixed[]
     */
    private $images = [];

    /**
     * @param mixed[] $image
     */
    public function addImage(array $image): void
    {
        $this->images[] = $image;
    }

    /**
     * @return mixed[]
     */
    public function getImages(): array
    {
        return $this->images;
    }
}
