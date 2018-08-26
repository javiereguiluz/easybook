<?php declare(strict_types=1);

namespace Easybook\Guard;

use Easybook\Exception\Configuration\MissingParameterException;

final class BookGuard
{
    /**
     * @param mixed[] $bookParameter
     */
    public function ensureBookParameterHasKey(array $bookParameter, string $key): void
    {
        if (isset($bookParameter[$key])) {
            return;
        }

        throw new MissingParameterException(sprintf(
            'Book is missing "%s" key. Have you defined it in "parameters > book > %s"?',
            $key,
            $key
        ));
    }
}
