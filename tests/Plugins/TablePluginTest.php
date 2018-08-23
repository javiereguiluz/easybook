<?php declare(strict_types=1);

namespace Easybook\Tests\Plugins;

use Iterator;
use Easybook\Configuration\CurrentItemProvider;
use Easybook\DependencyInjection\Application;
use Easybook\Events\ParseEvent;
use Easybook\Plugins\TablePlugin;
use Easybook\Plugins\TablePluginEventSubscriber;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Easybook\Tests\AbstractCustomConfigContainerAwareTestCase;
use Symfony\Component\EventDispatcher\Event;
use Symplify\PackageBuilder\Parameter\ParameterProvider;

final class TablePluginTest extends AbstractCustomConfigContainerAwareTestCase
{
    /**
     * @var CurrentItemProvider
     */
    private $currentItemProvider;

    /**
     * @var TablePluginEventSubscriber
     */
    private $tablePluginEventSubscriber;

    /**
     * @var ParameterProvider
     */
    private $parameterProvider;

    protected function setUp(): void
    {
        $this->currentItemProvider = $this->container->get(CurrentItemProvider::class);
        $this->tablePluginEventSubscriber = $this->container->get(TablePluginEventSubscriber::class);
        $this->parameterProvider = $this->container->get(ParameterProvider::class);
    }

    /**
     * @dataProvider getTestTablePluginData()
     *
     * @param mixed[] $expectedLabels
     */
    public function testTablePlugin(
        string $inputFilePath,
        string $expectedFilePath,
        int $itemNumber,
        bool $addLabels,
        array $expectedLabels
    ): void
    {
        $this->currentItemProvider->setItem([
            'config' => ['number' => $itemNumber],
            'content' => file_get_contents(__DIR__ . '/fixtures/tables/' . $inputFilePath),
        ]);

        $this->tablePluginEventSubscriber->decorateAndLabelTables(new ParseEvent());

        $item = $this->currentItemProvider->getItem();

        $this->assertSame(file_get_contents(__DIR__ . '/fixtures/tables/' . $expectedFilePath), $item['content']);

        $publishingListTables = $this->parameterProvider->provideParameter('publishing.list.tables');
        foreach ($publishingListTables as $i => $table) {
            $this->assertRegexp('/<table.*<\/table>/s', $table[$i]['item']['content']);

            if ($addLabels) {
                $this->assertSame($expectedLabels[$i], $table[$i]['item']['label']);
            }
        }
    }

    public function getTestTablePluginData(): Iterator
    {
        yield ['input_1.html', 'expected_1_1.html', 1, true, ['Table 1.1', 'Table 1.2']];
        yield ['input_1.html', 'expected_1_2.html', 2, true, ['Table 2.1', 'Table 2.2']];
        yield ['input_1.html', 'expected_1_1.html', 1, false, []];
        yield ['input_2.html', 'expected_2.html', 1, false, []];
        yield ['input_3.html', 'expected_3.html', 'A', true, ['Table A.1']];
    }

    protected function provideConfig(): string
    {
        return __DIR__ . '/custom-config.yml';
    }
}
