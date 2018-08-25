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
     * @var string
     */
    private $tableLabel;

    public function __construct(
        Renderer $renderer,
        Slugger $slugger,
        TablesProvider $tablesProvider,
        string $tableLabel
    ) {
        $this->renderer = $renderer;
        $this->slugger = $slugger;
        $this->tablesProvider = $tablesProvider;
        $this->tableLabel = $tableLabel;
    }

    public static function getSubscribedEvents(): Iterator
    {
        yield EasybookEvents::POST_PARSE => ['decorateAndLabelTables', -500];
    }

    /**
     * It decorates each table with a template and, if the edition configures it,
     * with the appropriate auto-numbered label.
     */
    public function decorateAndLabelTables(ItemAwareEvent $itemAwareEvent): void
    {
        $item = $itemAwareEvent->getItem();

        // ...
//        edition()
//            // tady
//
//            =>
//
//        foreach ($edistion as $edition) {
//            // tady
//        }

//        $addTableLabels = in_array('table', $itemAwareEvent->app->edition('labels') ?: [], true);
        $parentItemNumber = $item->getConfigNumber();

        $this->counter = 0;

        $item->changeContent(Strings::replace(
            $item->getContent(),
            "#(?<content><table.*\n<\/table>)#Ums",
            function ($matches) use ($parentItemNumber) {
                // prepare table parameters for template and label
                $this->counter++;
                $parameters = [
                    'item' => [
                        'caption' => '',
                        'content' => $matches['content'],
                        'label' => $this->tableLabel,
                        'number' => $this->counter,
                        'slug' => $this->slugger->slugify('Table ' . $parentItemNumber . '-' . $this->counter),
                    ],
                    'element' => [
                        'number' => $parentItemNumber,
                    ],
                ];

                // add table details to the list-of-tables
                $this->tablesProvider->addTable($parameters);

                return $this->renderer->render('table.twig', $parameters);
            }
        ));
    }
}
