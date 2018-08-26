<?php declare(strict_types=1);

namespace Easybook\Book\Factory;

use Easybook\Book\Edition;

final class EditionFactory
{
    /**
     * @param mixed[] $editionParameter
     */
    public function createFromEditionParameter(array $editionParameter): Edition
    {
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
