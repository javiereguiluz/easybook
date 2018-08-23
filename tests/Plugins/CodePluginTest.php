<?php declare(strict_types=1);

namespace Easybook\Tests\Plugins;

use Easybook\Events\ParseEvent;
use Easybook\Parsers\MarkdownParser;
use Easybook\Plugins\CodePluginEventSubscriber;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Twig\Parser;

final class CodePluginTest extends AbstractContainerAwareTestCase
{
    /**
     * @var MarkdownParser
     */
    private $markdownParser;

    protected function setUp(): void
    {
        $this->markdownParser = $this->container->get(MarkdownParser::class);
        $this->codePluginEventSubscriber = $this->container->get(CodePluginEventSubscriber::class);
    }

    /**
     * @dataProvider getCodeBlockConfiguration
     *
     * @param string $inputFilePath        The contents to be parsed
     * @param string $expectedFilePath     The expected result of parsing the contents
     * @param string $codeBlockType        The type of code block used in the content
     * @param bool   $enableCodeHightlight Whether or not code listings should be highlighted
     */
    public function testCodeBlocksTypes(
        string $inputFilePath,
        string $expectedFilePath,
        string $codeBlockType,
        bool $enableCodeHightlight
    ): void {
        $fixturesDir = __DIR__ . '/fixtures/code/';

        $app = $this->getApp($codeBlockType, $enableCodeHightlight);
        $plugin = new CodePluginEventSubscriber();
        $event = new ParseEvent($app);

        $event->setItem([
            'config' => ['format' => 'md'],
            'original' => file_get_contents($fixturesDir . '/' . $inputFilePath),
            'content' => '',
        ]);

        // execute pre-parse method of the plugin
        $plugin->parseCodeBlocks($event);
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
            ['input_1.md', 'expected_easybook_type_disabled_highlight.html', 'easybook', false],
            ['input_1.md', 'expected_easybook_type_enabled_highlight.html', 'easybook', true],

            ['input_2.md', 'expected_fenced_type_disabled_highlight.html', 'fenced', false],
            ['input_2.md', 'expected_fenced_type_enabled_highlight.html', 'fenced', true],

            ['input_3.md', 'expected_github_type_disabled_highlight.html', 'github', false],
            ['input_3.md', 'expected_github_type_enabled_highlight.html', 'github', true],
        ];
    }

    private function getApp($codeBlockType, $enableCodeHightlight)
    {
        $app = new Application();

        $app['publishing.book.slug'] = 'test_book';
        $app['publishing.edition'] = 'test_edition';
        $app['publishing.book.config'] = [
            'book' => [
                'slug' => 'test_book',
                'language' => 'en',
                'editions' => [
                    'test_edition' => [
                        'format' => 'html',
                        'highlight_cache' => false,
                        'highlight_code' => $enableCodeHightlight,
                        'theme' => 'clean',
                    ],
                ],
            ],
        ];

        // don't try to optimize the following code or you'll end up
        // with this error: 'Indirect modification of overloaded element'
        $parserOptions = $app['parser.options'];
        $parserOptions['code_block_type'] = $codeBlockType;
        $app['parser.options'] = $parserOptions;

        return $app;
    }
}
