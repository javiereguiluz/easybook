<?php declare(strict_types=1);

namespace Easybook\Book\Factory;

use Easybook\Book\Edition;
use Easybook\Guard\EditionGuard;

final class EditionFactory
{
    /**
     * @var EditionGuard
     */
    private $editionGuard;

    public function __construct(EditionGuard $editionGuard)
    {
        $this->editionGuard = $editionGuard;
    }

    /**
     * @param mixed[] $editionParameter
     */
    public function createFromEditionParameter(array $editionParameter, string $name): Edition
    {
        $this->editionGuard->ensureEditionParameterHasKey($editionParameter, 'format', $name);

        // @todo require params
        $edition = new Edition($editionParameter['format']);

        if (isset($editionParameter['before_publish'])) {
            $edition->addBeforePublishScripts((array) $editionParameter['before_publish']);
        }

        if (isset($editionParameter['after_publish'])) {
            $edition->addAfterPublishScripts((array) $editionParameter['after_publish']);
        }

        return $edition;
    }
}
