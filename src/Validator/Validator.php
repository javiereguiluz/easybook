<?php declare(strict_types=1);

namespace Easybook\Validator;

use RuntimeException;

final class Validator
{
    /**
     * Validates that the book represented by the given $slug exists in $dir directory.
     */
    public function validateBookDir(string $slug, string $baseDir): void
    {
        $bookDir = $baseDir . '/' . $slug;
        if (file_exists($bookDir)) {
            return;
        }

        throw new RuntimeException(sprintf(
            "The directory of the book cannot be found. Check that '%s' directory has a folder named as the book slug ('%s')",
            $baseDir,
            $slug
        ));
    }
}
