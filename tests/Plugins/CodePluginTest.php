<?php declare(strict_types=1);

namespace Easybook\Tests\Plugins;

use Easybook\Configuration\CurrentItemProvider;
use Easybook\Events\ParseEvent;
use Easybook\Parsers\MarkdownParser;
use Easybook\Plugins\CodePluginEventSubscriber;
use Easybook\Tests\AbstractContainerAwareTestCase;

final class CodePluginTest extends AbstractContainerAwareTestCase
{
    /**
     * @var MarkdownParser
     */
    private $markdownParser;

    /**
     * @var CodePluginEventSubscriber
     */
    private $codePluginEventSubscriber;

    /**
     * @var CurrentItemProvider
     */
    private $currentItemProvider;

    protected function setUp(): void
    {
        $this->markdownParser = $this->container->get(MarkdownParser::class);
        $this->codePluginEventSubscriber = $this->container->get(CodePluginEventSubscriber::class);
        $this->currentItemProvider = $this->container->get(CurrentItemProvider::class);
    }

    /**
     * @dataProvider getCodeBlockConfiguration()
     *
     * @param string $inputFilePath        The contents to be parsed
     * @param string $expectedFilePath     The expected result of parsing the contents
     * @param bool   $enableCodeHightlight Whether or not code listings should be highlighted
     */
    public function testCodeBlocksTypes(
        string $inputFilePath,
        string $expectedFilePath,
        bool $enableCodeHightlight
    ): void {
        $fixturesDir = __DIR__ . '/fixtures/code/';

        $app = $this->getApp($enableCodeHightlight);

        $this->currentItemProvider->setItem([
            'config' => ['format' => 'md'],
            'original' => file_get_contents($fixturesDir . '/' . $inputFilePath),
            'content' => '',
        ]);

        // execute pre-parse method of the plugin
        $this->codePluginEventSubscriber->parseCodeBlocks(new ParseEvent());
        $item = $event->getItem();

        // parse the item original content
        $item['content'] = $this->markdownParser->transform($item['original']);

        // execute post-parse method of the plugin
        $event->setItem($item);
        $plugin->fixParsedCodeBlocks($event);
        $item = $event->getItem();

        $this->assertSame(file_get_contents($fixturesDir . '/' . $expectedFilePath), $item['content']);
    }

    public function getCodeBlockConfiguration()
    {
        return [
            ['input_1.md', 'expected_easybook_type_disabled_highlight.html', false],
            ['input_1.md', 'expected_easybook_type_enabled_highlight.html', true],

            ['input_2.md', 'expected_fenced_type_disabled_highlight.html', false],
            ['input_2.md', 'expected_fenced_type_enabled_highlight.html', true],

            ['input_3.md', 'expected_github_type_disabled_highlight.html', false],
            ['input_3.md', 'expected_github_type_enabled_highlight.html', true],
        ];
    }

    private function getApp($enableCodeHightlight)
    {
//        $app = new Application();

        $app['publishing.book.slug'] = 'test_book';
        $app['publishing.edition'] = 'test_edition';
        $app['publishing.book.config'] = [
            'book' => [
                'slug' => 'test_book',
                'language' => 'en',
                'editions' => [
                    'test_edition' => [
                        'format' => 'html',
                        'highlight_code' => $enableCodeHightlight,
                        'theme' => 'clean',
                    ],
                ],
            ],
        ];

        return $app;
    }
}
