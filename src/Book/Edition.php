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
     * @param string[] $beforePublishScripts
     */
    public function addBeforePublishScripts(array $beforePublishScripts): void
    {
        $this->beforePublishScripts = $beforePublishScripts;
    }

    /**
     * @param string[] $afterPublishScripts
     */
    public function addAfterPublishScripts(array $afterPublishScripts): void
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
}
