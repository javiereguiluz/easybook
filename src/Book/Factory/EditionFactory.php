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
            $edition->setBeforePublishScripts((array) $editionParameter['before_publish']);
        }

        if (isset($editionParameter['after_publish'])) {
            $edition->setAfterPublishScripts((array) $editionParameter['after_publish']);
        }

        if (isset($editionParameter['images_base_dir'])) {
            $edition->setImagesBaseDir((string) $editionParameter['images_base_dir']);
        }

        return $edition;
    }
}
