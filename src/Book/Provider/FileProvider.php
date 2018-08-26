<?php declare(strict_types=1);

namespace Easybook\Book\Provider;

final class FileProvider
{
    /**
     * @var string
     */
    private $bookTemplatesDir;

    public function __construct(string $bookTemplatesDir)
    {
        $this->bookTemplatesDir = $bookTemplatesDir;
    }

    /*
     * If the book overrides the given templateName, this method returns the path
     * of the custom template.
     */
    public function getCustomTemplate(string $templateName): ?string
    {
        $paths = [
            $this->bookTemplatesDir . DIRECTORY_SEPARATOR . $this['publishing.edition'],
            $this->bookTemplatesDir . DIRECTORY_SEPARATOR . $this->edition('format'),
            $this->bookTemplatesDir,
        ];

        return $this->getFirstExistingFile($templateName, $paths);
    }

    /**
     * @param string[] $paths
     */
    private function getFirstExistingFile(string $file, array $paths): ?string
    {
        foreach ($paths as $path) {
            if (file_exists($path . DIRECTORY_SEPARATOR . $file)) {
                return realpath($path . DIRECTORY_SEPARATOR . $file);
            }
        }

        return null;
    }
}
