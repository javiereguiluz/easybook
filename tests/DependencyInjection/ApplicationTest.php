<?php declare(strict_types=1);

namespace Easybook\Tests\DependencyInjection;

use Easybook\Templating\Renderer;
use Easybook\Tests\AbstractContainerAwareTestCase;
use Symfony\Component\Yaml\Yaml;

final class ApplicationTest extends AbstractContainerAwareTestCase
{
    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var string
     */
    private $translationsDir;

    protected function setUp(): void
    {
        $this->renderer = $this->container->get(Renderer::class);
        $this->translationsDir = $this->container->getParameter('translations_dir');
    }

    public function testGetTitleMethodForDefaultTitles(): void
    {
        $files = $this->finder->files()
            ->name('titles.*.yml')
            ->in($this->translationsDir)
            ->getIterator();

        foreach ($files as $file) {
            $locale = substr($file->getRelativePathname(), -6, 2);

            // reset the application for each language because titles are cached
            $app['publishing.edition'] = 'edition1';
            $app['publishing.book.config'] = ['book' => [
                'language' => $locale,
                'editions' => [
                    'edition1' => [],
                ],
            ]];

            $titles = Yaml::parse($file->getContents());
            foreach ($titles['title'] as $key => $expectedValue) {
                $this->assertSame($expectedValue, $app->getTitle($key));
            }
        }
    }

    public function testGetLabelMethodForDefaultLabels(): void
    {
        $files = $this->finder->files()
            ->name('labels.*.yml')
            ->in($this->translationsDir)
            ->getIterator();

        $labelVariables = [
            'item' => [
                'number' => 1,
                'counters' => [1, 1, 1, 1, 1, 1],
                'level' => 1,
            ],
            'element' => [
                'number' => 1,
            ],
        ];

        foreach ($files as $file) {
            $locale = substr($file->getRelativePathname(), -6, 2);

            // reset the application for each language because labels are cached
            $app['publishing.edition'] = 'edition1';
            $app['publishing.book.config'] = ['book' => [
                'language' => $locale,
                'editions' => [
                    'edition1' => [],
                ],
            ]];

            $labels = Yaml::parse($file->getContents());
            foreach ($labels['label'] as $key => $value) {
                // some labels (chapter and appendix) are arrays instead of strings
                if (is_array($value)) {
                    foreach ($value as $i => $subLabel) {
                        $expectedValue = $this->renderer->render($subLabel, $labelVariables);
                        $labelVariables['item']['level'] = $i + 1;

                        $this->assertSame($expectedValue, $app->getLabel($key, $labelVariables));
                    }
                } else {
                    $expectedValue = $this->renderer->render($value, $labelVariables);

                    $this->assertSame($expectedValue, $app->getLabel($key, $labelVariables));
                }
            }
        }
    }

    public function testGetPublishingEditionId(): void
    {
        // get the ID of a ISBN-less book
        $app['publishing.edition'] = 'edition1';
        $app['publishing.book.config'] = ['book' => [
            'editions' => [
                'edition1' => [],
            ],
        ]];

        $publishingId = $app['publishing.edition.id'];

        $this->assertSame('URN', $publishingId['scheme']);
        $this->assertSame(36, strlen($publishingId['value']));
        $this->assertRegExp('/[a-f0-9\-]*/', $publishingId['value']);

        // get the ID of a book with an ISBN
        // @load with config "edition > isbn > 9782918390060"

        $publishingId = $app['publishing.edition.id'];

        $this->assertSame('isbn', $publishingId['scheme']);
        $this->assertSame('9782918390060', $publishingId['value']);
    }
}
