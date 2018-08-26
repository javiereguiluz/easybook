<?php declare(strict_types=1);

namespace Easybook\Book;

final class Edition
{
    /**
     * @var string[]
     */
    private $beforePublishScripts = [];

    /**
     * @var string[]
     */
    private $afterPublishScripts = [];

    /**
     * @var string
     */
    private $format;

    /**
     * @var string
     */
    private $imagesBaseDir;

    public function __construct(string $format)
    {
        $this->format = $format;
    }

    /**
     * @param string[] $beforePublishScripts
     */
    public function setBeforePublishScripts(array $beforePublishScripts): void
    {
        $this->beforePublishScripts = $beforePublishScripts;
    }

    /**
     * @param string[] $afterPublishScripts
     */
    public function setAfterPublishScripts(array $afterPublishScripts): void
    {
        $this->afterPublishScripts = $afterPublishScripts;
    }

    /**
     * @return string[]
     */
    public function getBeforePublishScripts(): array
    {
        return $this->beforePublishScripts;
    }

    /**
     * @return string[]
     */
    public function getAfterPublishScripts(): array
    {
        return $this->afterPublishScripts;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setImagesBaseDir(string $imagesBaseDir): void
    {
        $this->imagesBaseDir = $imagesBaseDir;
    }

    public function getImagesBaseDir(): ?string
    {
        return $this->imagesBaseDir;
    }
}
