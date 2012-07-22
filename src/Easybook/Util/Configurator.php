<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Util;

use Symfony\Component\Yaml\Yaml;

/**
 * Resolves book configuration by merging all the different configuration
 * sources (CLI command options, YAML configuration file and default values).
 */
class Configurator
{
    private $app;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Resolves book configuration options by parsing their values as Twig strings.
     * easybook allows to use Twig expressions as the value of options. For example:
     * { "book": { "title": "{{ book.author }} diary", "author": "...", ... } }
     */
    public function resolveConfiguration()
    {
        $config = $this->app->get('book');

        // prepare options needed to parse option values as Twig expressions
        $theApp = clone $this->app;
        $twigOptions = array(
            'book'    => $config,
            'edition' => $config['editions'][$this->app->get('publishing.edition')]
        );

        array_walk_recursive($config, function (&$value, $key) use ($theApp, $twigOptions) {
            // this condition is needed to prevent the conversion performed by
            // Twig to some special values: true => 1, false => 0, null => (nothing)
            if (true !== $value && false !== $value && null !== $value) {
                $value = $theApp->renderString($value, $twigOptions);
            }
        });

        // load final book configuration into application
        $this->app->set('book', $config);

        // perform the last global validation
        $this->app->get('validator')->validateResolvedConfiguration();
    }

    /**
     * Loads book configuration from three different sources:
     *   1. '--configuration' option from 'publish' command
     *   2. config.yml configuration file
     *   3. easybook default options
     *
     * In addition, it validates the loaded options.
     */
    public function loadBookConfiguration()
    {
        $bookDir = $this->app->get('publishing.dir.book');
        $slug    = $this->app->get('publishing.book.slug');

        // 1. load options from '--configuration' command option
        // $ ./book publish my-book my-edition --configuration='{ "book": { "title": "My new title" } }'
        $commandConfig = array('book' => array());
        if (null != $configuration = $this->app->get('console.input')->getOption('configuration')) {
            $commandConfig = json_decode($configuration, true);
        }

        // 2. load options from 'config.yml' file
        $configFile = $bookDir.'/config.yml';
        if (!file_exists($configFile)) {
            throw new \RuntimeException(sprintf(
                "There is no 'config.yml' configuration file for '%s' book \n\n"
                ."Try to create the book again with the 'new' command or create \n"
                ."'%s' file by hand",
                $slug, realpath($bookDir).'/config.yml'
            ));
        }
        $fileConfig = Yaml::parse($configFile);

        // 3. load easybook default options for books
        $defaultConfig = $this->app->get('app.book.defaults');

        // TODO: use OptionsResolver component when it supports recursive merging
        // and allows to set options different from defaultValues.
        // $resolver = new \Symfony\Component\OptionsResolver\OptionsResolver();
        // easybook allows authors to define any configuration option under
        // 'book' key in 'config.yml' file. However, resolver will throw an
        // exception if there are options different from the default options.
        // The trick is to use 'setOptional()' method to set all book options
        // as optional. The author can then define as many options as needed.
        // $resolver->setOptional(array_keys($fileConfig['book'] ?: array()));
        // $config = $resolver->setDefaults($defaultConfig)->resolve($fileConfig['book'] ?: array());
        // $resolver->setOptional(array_keys($commandConfig['book'] ?: array()));
        // $config = $resolver->setDefaults($config)->resolve($commandConfig['book'] ?: array());

        // merge and resolve configuration options
        $config = array_replace_recursive(
            $defaultConfig,
            $fileConfig['book'] ?: array(),
            $commandConfig['book']
        );

        // validate book configuration
        $this->app->get('validator')->validateBookConfig($config);

        // load book configuration into the application
        $this->app->set('book', $config);
    }

    /**
     * Loads edition configuration taking into account edition inheritance
     * and easybook default options for editions. Options set by
     * '--configuration' are already merged by 'loadBookConfiguration()' method.
     *
     * In addition, it validates the loaded options.
     */
    public function loadEditionConfiguration()
    {
        $book    = $this->app->get('book');
        $edition = $this->app->get('publishing.edition');

        // 1. load current edition configuration
        $editionConfig = $book['editions'][$edition] ?: array();

        // 2. resolve edition inheritance
        $parentConfig = array();
        if (null != $parent = $this->app->edition('extends')) {
            if (!array_key_exists($parent, $book['editions'])) {
                throw new \UnexpectedValueException(sprintf(
                    " ERROR: '%s' edition extends nonexistent '%s' edition"
                    ."\n\n"
                    ."Check in '%s' file \n"
                    ."that the value of 'extends' option in '%s' edition is a valid \n"
                    ."edition of the book",
                    $edition, $parent, realpath($this->app->get('publishing.dir.book').'/config.yml'), $edition
                ));
            }

            $parentConfig = $book['editions'][$parent] ?: array();
        }

        // 3. load easybook default options for editions
        $defaultConfig = $this->app->get('app.edition.defaults');

        // TODO: use OptionsResolver component when it supports recursive merging
        // and allows to set options different from defaultValues.
        // $resolver = new \Symfony\Component\OptionsResolver\OptionsResolver();
        // easybook allows authors to define any configuration option under
        // 'edition' key in 'config.yml' file. However, resolver will throw an
        // exception if there are options different from the default options.
        // The trick is to use 'setOptional()' method to set all edition options
        // as optional. The author can then define as many options as needed.
        // $resolver->setOptional(array_keys($parentConfig ?: array()));
        // $config = $resolver->setDefaults($defaultConfig)->resolve($parentConfig ?: array());
        // $resolver->setOptional(array_keys($config ?: array()));
        // $config = $resolver->setDefaults($config)->resolve($editionConfig ?: array());

        // merge and resolve configuration options
        $config = array_replace_recursive(
            $defaultConfig,
            $parentConfig,
            $editionConfig
        );

        // validate edition configuration
        $this->app->get('validator')->validateEditionConfig($config);

        // load resolved edition configuration into the application
        $book['editions'][$edition] = $config;
        $this->app->set('book', $book);
    }
}
