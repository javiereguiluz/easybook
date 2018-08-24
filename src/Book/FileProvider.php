<?php declare(strict_types=1);

namespace Easybook\Book;

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
            $this->bookTemplatesDir . '/' . $this['publishing.edition'],
            $this->bookTemplatesDir . '/' . $this->edition('format'),
            $this->bookTemplatesDir,
        ];

        return $this->getFirstExistingFile($templateName, $paths);
    }

    public function getCustomLabelsFile(): ?stringetFirstExistingFi
    {
        $labelsFileName = 'labels.' . $this->book('language') . '.yml';
        $paths = [
            $this['book_resources_dir'] . '/Translations/' . $this['publishing.edition'],
            $this['book_resources_dir'] . '/Translations/' . $this->edition('format'),
            $this['book_resources_dir'] . '/Translations',
        ];

        return $this->getFirstExistingFile($labelsFileName, $paths);
    }

    public function getCustomTitlesFile(): ?string
    {
        $titlesFileName = 'titles.' . $this->book('language') . '.yml';
        $paths = [
            $this['book_resources_dir'] . '/Translations/' . $this['publishing.edition'],
            $this['book_resources_dir'] . '/Translations/' . $this->edition('format'),
            $this['book_resources_dir'] . '/Translations',
        ];

        return $this->getFirstExistingFile($titlesFileName, $paths);
    }

    public function getCustomCoverImage(): ?string
    {
        $coverFileName = 'cover.jpg';
        $paths = [
            $this->bookTemplatesDir . '/' . $this['publishing.edition'],
            $this->bookTemplatesDir . '/' . $this->edition('format'),
            $this->bookTemplatesDir,
        ];

        return $this->getFirstExistingFile($coverFileName, $paths);
    }

    /**
     * @param string[] $paths
     */
    private function getFirstExistingFile(string $file, array $paths): ?string
    {
        foreach ($paths as $path) {
            if (file_exists($path . '/' . $file)) {
                return realpath($path . '/' . $file);
            }
        }

        return null;
    }
}
