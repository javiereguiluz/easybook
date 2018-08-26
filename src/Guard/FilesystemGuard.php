<?php declare(strict_types=1);

namespace Easybook\Guard;

use Easybook\Exception\Filesystem\DirectoryNotEmptyException;
use Easybook\Exception\Filesystem\DirectoryNotExistsException;
use Symfony\Component\Filesystem\Filesystem;

final class FilesystemGuard
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function ensureDirectoryIsEmpty(string $directory): void
    {
        if (! $this->filesystem->exists($directory)) {
            return;
        }

        if ((bool) glob($directory . '/*')) {
            throw new DirectoryNotEmptyException(sprintf(
                'Directory "%s" for new book is not empty. Delete it or choose a new one.',
                $directory
            ));
        }
    }

    public function ensureBookDirectoryExists(string $directory): void
    {
        if ($this->filesystem->exists($directory)) {
            return;
        }

        throw new DirectoryNotExistsException(sprintf(
            'Book directory "%s" does not exist. Fix the path of first create by "%s" command',
            $directory,
            'vendor/bin/easybook new ' . $directory
        ));
    }
}
