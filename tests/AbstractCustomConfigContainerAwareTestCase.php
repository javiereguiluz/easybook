<?php declare(strict_types=1);

namespace Easybook\Tests;

use Easybook\DependencyInjection\ContainerFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

abstract class AbstractCustomConfigContainerAwareTestCase extends TestCase
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @param mixed[] $data
     */
    public function __construct(?string $name = null, array $data = [], string $dataName = '')
    {
        $this->container = (new ContainerFactory())->createFromConfig($this->provideConfig());

        parent::__construct($name, $data, $dataName);
    }

    abstract protected function provideConfig(): string;
}
