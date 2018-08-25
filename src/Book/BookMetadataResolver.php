<?php declare(strict_types=1);

namespace Easybook\Book;

use Easybook\Templating\Renderer;

final class BookMetadataResolver
{
    /**
     * @var Renderer
     */
    private $renderer;

    public function __construct(Renderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Shortcut method to get the label of any element type.
     *
     * @param string $element   The element type ('chapter', 'foreword', ...)
     * @param mixed[] $variables Optional variables used to render the label
     *
     * @return string The label of the element or an empty string
     */
    public function getLabel(string $element, array $variables = []): string
    {
        $label = $this['labels']['label'][$element]
            ?? '';

        // some elements (mostly chapters and appendices) have a different label for each level (h1, ..., h6)
        if (is_array($label)) {
            $index = $variables['item']['level'] - 1;
            $label = $label[$index];
        }

        return $this->renderer->render($label, $variables);
    }

    /**
     * Shortcut method to get the title of any element type.
     *
     * @param string $element The element type ('chapter', 'foreword', ...)
     *
     * @return string The title of the element or an empty string
     */
    public function getTitle(string $element): string
    {
        return $this['titles']['title'][$element] ?? '';

        $bookConfig = $this['publishing.book.config'];
        if ($bookConfig !== null) {
            $twig->addGlobal('book', $bookConfig['book']);

            $publishingEdition = $this['publishing.edition'];
            $editions = $this->book('editions');
            $twig->addGlobal('edition', $editions[$publishingEdition]);
        }

        return $twig->render($string, $variables);
    }
}
