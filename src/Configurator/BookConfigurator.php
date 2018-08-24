<?php declare(strict_types=1);

namespace Easybook\Configurator;

use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use UnexpectedValueException;

final class BookConfigurator
{
    /**
     * It loads the inline configuration that can be set via the --configuration
     * command option.
     *
     * $ ./book publish my-book my-edition --configuration='{ "book": { "title": "My new title" } }'
     *
     * @param string $configurationJsonString The configuration set via the console command option (in JSON)
     *
     * @return array The loaded configuration (or an empty array if no configuration is set)
     */
    public function loadCommandConfiguration(string $configurationJsonString): array
    {
        $config = json_decode($configurationJsonString, true);

        return empty($config) ? [] : $config;
    }

    /**
     * It loads the configuration values set via the book's config.yml file.
     *
     * @param string $bookDir The root dir of the book being published
     *
     * @return array The loaded configuration.
     *
     * @throws \RuntimeException If no config.yml is present.
     */
    public function loadBookFileConfiguration(string $bookDir): array
    {
        $bookConfigFile = $bookDir . '/config.yml';

        if (! file_exists($bookConfigFile)) {
            throw new RuntimeException(sprintf(
                "There is no 'config.yml' configuration file for '%s' book \n\n"
                . "Try to create the book again with the 'new' command or create \n"
                . "'%s' file by hand",
                $this->app['publishing.book.slug'],
                realpath($bookDir) . '/config.yml'
            ));
        }

        $config = Yaml::parse($bookConfigFile);

        return empty($config) ? [] : $config;
    }

    /**
     * It loads edition configuration by merging all the different configuration
     * sources (config.yml configuration, edition inheritance and default configuration).
     *
     * @return array The complete book configuration (this method only fills-in the edition configuration)
     */
    public function loadEditionConfiguration(): array
    {
        $bookConfiguration = $this->app['publishing.book.config'];
        $edition = $this->app['publishing.edition'];

        if (! isset($bookConfiguration['book']['editions'][$edition])) {
            throw new RuntimeException(sprintf(
                "ERROR: The '%s' edition isn't defined for\n"
                    . "'%s' book.",
                $edition,
                $this->app->book('title')
            ));
        }

        $editionConfig = $bookConfiguration['book']['editions'][$edition] ?: [];
        $parentEditionConfig = $this->loadParentEditionConfiguration();
        $defaultConfig = $this->loadDefaultEditionConfiguration();

        $configuration = array_replace_recursive($defaultConfig, $parentEditionConfig, $editionConfig);

        $bookConfiguration['book']['editions'][$edition] = $configuration;

        return $bookConfiguration;
    }

    /**
     * It resolves the edition inheritance (if any) and loads the parent edition
     * configuration.
     *
     * @return array The configuration of the parent edition (or an empty array)
     *
     * @throws \UnexpectedValueException If the edition extends an undefined edition.
     */
    public function loadParentEditionConfiguration(): array
    {
        $bookEditions = $this->app->book('editions');
        $edition = $this->app['publishing.edition'];

        $parentEdition = $this->app->edition('extends');
        $parentEditionConfig = [];

        if ($parentEdition !== null) {
            if (! isset($bookEditions[$parentEdition])) {
                throw new UnexpectedValueException(sprintf(
                    "'%s' edition extends nonexistent '%s' edition"
                        . "\n\n"
                        . "Check in '%s' file" . PHP_EOL
                        . "that the value of 'extends' option in '%s' edition is a valid \n"
                        . 'edition of the book',
                    $edition,
                    $parentEdition,
                    realpath($this->app['publishing.dir.book'] . '/config.yml'),
                    $edition
                ));
            }

            $parentEditionConfig = $bookEditions[$parentEdition] ?: [];
        }

        return $parentEditionConfig;
    }

    /**
     * It loads the default configuration options for the editions.
     */
    public function loadDefaultEditionConfiguration(): array
    {
        $config = Yaml::parse(__DIR__ . '/DefaultConfigurations/edition.yml');

        return $config['edition'] ?: [];
    }

    /**
     * * It parses all the configuration values as if they were Twig strings, because
     * easybook allows to use Twig expressions as the value of options. For example:.
     *
     * { "book": { "title": "{{ book.author }} diary", "author": "...", ... } }
     *
     * @return array The complete book configuration with all its dynamic/variable values resolved
     */
    public function processConfigurationValues(): array
    {
        $bookConfig = $this->app['publishing.book.config'];
        $editionConfig = $bookConfig['book']['editions'][$this->app['publishing.edition']];

        // prepare options needed to parse option values as Twig expressions
        $twig_variables = [
            'book' => $bookConfig['book'],
            'edition' => $editionConfig,
        ];

        foreach ($bookConfig['book'] as $key => $value) {
            if ($value !== true && $value !== false && $value !== null && ! is_array($value)) {
                $bookConfig['book'][$key] = $app->renderString($value, $twig_variables);
            } elseif (is_array($value)) {
                foreach ($value as $subkey => $subvalue) {
                    if ($subvalue !== true && $subvalue !== false && $subvalue !== null && ! is_array($subvalue)) {
                        $bookConfig['book'][$key][$subkey] = $app->renderString($subvalue, $twig_variables);
                    }
                }
            }
        }

        return $bookConfig;
    }
}
