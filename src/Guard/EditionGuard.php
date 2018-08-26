<?php declare(strict_types=1);

namespace Easybook\Guard;

use Easybook\Exception\Configuration\MissingParameterException;

final class EditionGuard
{
    /**
     * @param mixed[] $editionParameter
     */
    public function ensureEditionParameterHasKey(array $editionParameter, string $key, string $name): void
    {
        if (isset($editionParameter[$key])) {
            return;
        }

        throw new MissingParameterException(sprintf(
            'Edition is missing "%s" key. Have you defined it in "parameters > book > editions > %s > %s"?',
            $key,
            $name,
            $key
        ));
    }
}
