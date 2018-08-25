<?php declare(strict_types=1);

namespace Easybook\Plugins;

use Easybook\Book\Provider\TablesProvider;
use Easybook\Events\EasybookEvents;
use Easybook\Events\ItemAwareEvent;
use Easybook\Templating\Renderer;
use Easybook\Util\Slugger;
use Iterator;
use Nette\Utils\Strings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * It performs some operations on the book tables, such as decorating their contents and adding labels to them.
 */
final class TablePluginEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var Slugger
     */
    private $slugger;

    /**
     * @var int
     */
    private $counter = 0;

    /**
     * @var TablesProvider
     */
    private $tablesProvider;

    /**
     * @var mixed[]
     */
    private $labels = [];

    /**
     * @param mixed[] $labels
     */
    public function __construct(
        Renderer $renderer,
        Slugger $slugger,
        TablesProvider $tablesProvider,
        array $labels
    ) {
        $this->renderer = $renderer;
        $this->slugger = $slugger;
        $this->tablesProvider = $tablesProvider;
        $this->labels = $labels;
    }

    public static function getSubscribedEvents(): Iterator
    {
        yield EasybookEvents::POST_PARSE => ['decorateAndLabelTables', -500];
    }

    /**
     * It decorates each table with a template and with the appropriate auto-numbered label.
     */
    public function decorateAndLabelTables(ItemAwareEvent $itemAwareEvent): void
    {
        $item = $itemAwareEvent->getItem();

        $parentItemNumber = $item->getConfigNumber();

        $this->counter = 0;

        $item->changeContent(Strings::replace(
            $item->getContent(),
            "#(?<content><table.*\n<\/table>)#Ums",
            function (array $matches) use ($parentItemNumber): string {
                // prepare table parameters for template and label
                $this->counter++;

                // @todo object?
                $parameters = [
                    'item' => [
                        'caption' => '',
                        'content' => $matches['content'],
                        'label' => $this->labels['table'],
                        'number' => $this->counter,
                        'slug' => $this->slugger->slugify('Table ' . $parentItemNumber . '-' . $this->counter),
                    ],
                    'element' => [
                        'number' => $parentItemNumber,
                    ],
                ];

                // render the label
                if ($parameters['item']['label']) {
                    $parameters['item']['label'] = $this->renderer->render($parameters['item']['label'], $parameters);
                }

                // add table details to the list-of-tables
                $this->tablesProvider->addTable($parameters);

                return $this->renderer->render('table.twig', $parameters);
            }
        ));
    }
}
