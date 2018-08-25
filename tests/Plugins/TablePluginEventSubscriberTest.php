<?php declare(strict_types=1);

namespace Easybook\Tests\Plugins;

use Easybook\Book\Item;
use Easybook\Book\Provider\EditionProvider;
use Easybook\Book\Provider\TablesProvider;
use Easybook\Events\ItemAwareEvent;
use Easybook\Plugins\TablePluginEventSubscriber;
use Easybook\Tests\AbstractCustomConfigContainerAwareTestCase;
use Iterator;

final class TablePluginEventSubscriberTest extends AbstractCustomConfigContainerAwareTestCase
{
    /**
     * @var TablePluginEventSubscriber
     */
    private $tablePluginEventSubscriber;

    /**
     * @var TablesProvider
     */
    private $tablesProvider;

    /**
     * @var EditionProvider
     */
    private $editionProvider;

    protected function setUp(): void
    {
        $this->tablePluginEventSubscriber = $this->container->get(TablePluginEventSubscriber::class);
        $this->tablesProvider = $this->container->get(TablesProvider::class);
        $this->editionProvider = $this->container->get(EditionProvider::class);
    }

    /**
     * @dataProvider getTestTablePluginData()
     *
     * @param string[] $expectedLabels
     */
    public function testTablePlugin(
        string $inputFilePath,
        string $expectedFilePath,
        int $itemNumber,
        bool $addLabels,
        array $expectedLabels
    ): void {
        $this->editionProvider->setEdition('Pdf');

        $content = file_get_contents(__DIR__ . '/fixtures/tables/' . $inputFilePath);
        $item = Item::createFromConfigNumberAndContent($itemNumber, $content);

        $parseEvent = new ItemAwareEvent($item);
        $this->tablePluginEventSubscriber->decorateAndLabelTables($parseEvent);

        $this->assertStringEqualsFile(__DIR__ . '/fixtures/tables/' . $expectedFilePath, $item->getContent());

        foreach ($this->tablesProvider->getTables() as $i => $table) {
            $this->assertRegexp('/<table.*<\/table>/s', $table['item']['content']);

            if ($addLabels) {
                $this->assertSame($expectedLabels[$i], $table['item']['label']);
            }
        }
    }

    public function getTestTablePluginData(): Iterator
    {
        yield ['input_1.html', 'expected_1_1.html', 1, true, ['Table 1.1', 'Table 1.2']];
        yield ['input_1.html', 'expected_1_2.html', 2, true, ['Table 2.1', 'Table 2.2']];
        yield ['input_1.html', 'expected_1_1.html', 1, false, []];
        yield ['input_2.html', 'expected_2.html', 1, false, []];
    }

    protected function provideConfig(): string
    {
        return __DIR__ . '/custom-config.yml';
    }
}
