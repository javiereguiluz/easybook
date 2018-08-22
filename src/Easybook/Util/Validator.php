<?php declare(strict_types=1);

namespace Easybook\Util;

use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class Validator
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    public function __construct(InputInterface $input, OutputInterface $output, SymfonyStyle $symfonyStyle)
    {
        $this->input = $input;
        $this->output = $output;
        $this->symfonyStyle = $symfonyStyle;
    }

    public function validateDirExistsAndWritable($dir)
    {
        if ($dir === null || trim($dir) === '') {
            // it throws an exception for invalid values because it's used in console commands
            throw new InvalidArgumentException('The directory cannot be empty.');
        }

        if (! is_dir($dir)) {
            // it throws an exception for invalid values because it's used in console commands
            throw new InvalidArgumentException("'${dir}' directory doesn't exist.");
        }

        if (! is_writable($dir)) {
            // it throws an exception for invalid values because it's used in console commands
            throw new InvalidArgumentException("'${dir}' directory is not writable.");
        }

        return $dir;
    }

    /**
     * Validates that the given $slug is a valid string for a book slug.
     */
    public function validateBookSlug($slug)
    {
        if (! preg_match('/^[a-zA-Z0-9\-]+$/', $slug)) {
            // it throws an exception for invalid values because it's used in console commands
            throw new InvalidArgumentException('The slug can only contain letters, numbers and dashes (no spaces)');
        }

        return $slug;
    }

    /**
     * Validates that the book represented by the given $slug exists in $dir directory.
     */
    public function validateBookDir($slug, $baseDir)
    {
        $attempts = 6;
        $bookDir = $baseDir . '/' . $slug;

        if (! $this->input->isInteractive() && ! file_exists($bookDir)) {
            throw new RuntimeException(sprintf(
                "The directory of the book cannot be found.\n"
                . " Check that '%s' directory \n"
                . " has a folder named as the book slug ('%s')",
                $baseDir,
                $slug
            ));
        }

        // check that the given book already exists or ask for another slug
        while (! file_exists($bookDir) && $attempts--) {
            if (! $attempts) {
                throw new InvalidArgumentException(sprintf(
                    "Too many failed attempts of getting the book directory.\n"
                    . " Check that '%s' directory \n"
                    . " has a folder named as the book slug ('%s')",
                    $baseDir,
                    $slug
                ));
            }

            throw new RuntimeException(sprintf(
                'The given "%s" slug does not match any book in "%s" directory.',
                $slug,
                realpath($baseDir)
            ));

            $bookDir = $baseDir . '/' . $slug;
        }

        return $bookDir;
    }

    /**
     * Validates that the given $slug is a valid string for a edition slug.
     */
    public function validateEditionSlug($slug)
    {
        if (! preg_match('/^[a-zA-Z0-9\-\_]+$/', $slug)) {
            throw new InvalidArgumentException(
                'The edition name can only contain letters, numbers and dashes (no spaces)'
            );
        }

        return $slug;
    }

    /**
     * Validates that the given $edition is defined in the book configuration.
     */
    public function validatePublishingEdition($edition)
    {
        // if the book defines no edition, raise an exception
        if (count($this->app->book('editions') ?: []) === 0) {
            throw new RuntimeException(sprintf(
                " Book hasn't defined any edition.\n"
                . "\n"
                . " Check that your book has at least one edition defined under\n"
                . " 'editions' option in the following configuration file:\n"
                . "\n"
                . " '%s'",
                realpath($this->app['publishing.dir.book'] . '/config.yml')
            ));
        }

        if (! array_key_exists($edition, $this->app->book('editions'))) {
            if ($this->input->isInteractive()) {
                $edition = $this->askForPublishingEdition();
            } else {
                throw new RuntimeException(sprintf(
                    "The '%s' edition isn't defined for\n"
                        . "'%s' book.",
                    $edition,
                    $this->app->book('title')
                ));
            }
        }

        return $edition;
    }

    /**
     * Asks the user for a valid edition to be published. If the given edition
     * names are invalid, this method ask again several times before throwing
     * an exception.
     *
     * @return string The name of the edition to be published
     *
     * @throws \RuntimeException If there are too many failed attempts
     */
    private function askForPublishingEdition(): string
    {
        $attempts = 6;
        $bookDir = $this->app['publishing.dir.book'];
        $edition = '';

        // check that the book has defined the given edition or ask for another edition
        while (! array_key_exists($edition, $this->app->book('editions')) && $attempts--) {
            if (! $attempts) {
                throw new RuntimeException(sprintf(
                    " Too many failed attempts. Check that your book has a\n"
                    . " '%s' edition defined in the following configuration file:\n"
                    . " '%s'",
                    $edition,
                    realpath($bookDir . '/config.yml')
                ));
            }

            $this->symfonyStyle->error([
                '',
                " ERROR </> The <info>${edition}</info> edition isn't defined for "
                . '<comment>' . $this->app->book('title') . '</comment> book',
                '',
                ' Check that <comment>' . realpath($bookDir . '/config.yml') . '</comment> file',
                " defines a <info>${edition}</info> edition under the <info>editions</info> option.",
            ]);

            $edition = $this->app['console.dialog']->ask(
                $this->output,
                ["\n Please, type the name of the <info>edition</info> to be published:\n" . ' > ']
            );
        }

        return $edition;
    }
}
