<?php declare(strict_types=1);

namespace Easybook\Util;

final class BetterFilesystem
{
    public function getFirstExistingFile(string $file, array $paths): ?string
    {
        foreach ($paths as $path) {
            if (file_exists($path . '/' . $file)) {
                return realpath($path . '/' . $file);
            }
        }

        return null;
    }
}
