<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Publishers;

use Symfony\Component\Process\Process;

/**
 * It publishes the book as a MOBI file. All the internal links are transformed
 * into clickable cross-section book links.
 */
class MobiPublisher extends Epub2Publisher
{
    // Kindle Publishing Guidelines rule that ebooks
    // should contain an HTML TOC, so it cannot be excluded
    protected $excludedElements = array('cover', 'lot', 'lof');

    public function checkIfThisPublisherIsSupported()
    {
        if (null != $this->app['kindlegen.path'] && file_exists($this->app['kindlegen.path'])) {
            $kindleGenPath = $this->app['kindlegen.path'];
        } else {
            $kindleGenPath = $this->findKindleGenPath();
        }

        $this->app['kindlegen.path'] = $kindleGenPath;

        return null != $kindleGenPath && file_exists($kindleGenPath);
    }

    public function assembleBook()
    {
        parent::assembleBook();

        $epubFilePath = $this->app['publishing.dir.output'].'/book.epub';

        $command = sprintf("%s %s -o book.mobi %s",
            $this->app['kindlegen.path'],
            $this->app['kindlegen.command_options'],
            $epubFilePath
        );

        $process = new Process($command);
        $process->run();

        $this->app['console.output']->write("\n\n".$process->getOutput()."\n\n");

        // remove the book.epub file used to generate the book.mobi file
        $this->app['filesystem']->remove($epubFilePath);
    }

    /**
     * Looks for the executable of the Amazon KindleGen library.
     *
     * @return string The absolute path of the executable
     * @throws \RuntimeException If the KindleGen executable is not found
     */
    private function findKindleGenPath()
    {
        foreach ($this->app['kindlegen.default_paths'] as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // the executable couldn't be found in the common
        // installation directories. Ask the user for the path
        $isInteractive = null != $this->app['console.input'] && $this->app['console.input']->isInteractive();
        if (!$isInteractive) {
            $sampleYamlConfiguration = <<<YAML
  easybook:
      parameters:
          kindlegen.path: '/path/to/utils/KindleGen/kindlegen'

  book:
      title:  ...
      author: ...
      # ...
YAML;
            throw new \RuntimeException(sprintf(
                "ERROR: The KindleGen library needed to generate MOBI books cannot be found.\n"
                    ." Check that you have installed KindleGen in a common directory \n"
                    ." or set your custom KindleGen path in the book's config.yml file:\n\n"
                    ."%s",
                $sampleYamlConfiguration
            ));
        }

        return $this->askForKindleGenPath();
    }

    /**
     * @codeCoverageIgnore
     */
    private function askForKindleGenPath()
    {
        $this->app['console.output']->write(sprintf(
                " In order to generate MOBI ebooks, KindleGen library must be installed. \n\n"
                    ." We couldn't find KindleGen executable in any of the following directories: \n"
                    ."   -> %s \n\n"
                    ." If you haven't installed it yet, you can download it freely from Amazon at: \n"
                    ." %s \n\n"
                    ." If you have installed it in a custom directory, please type its full absolute path:\n > ",
                implode($this->app['kindlegen.default_paths'], "\n   -> "),
                'http://amzn.to/kindlegen'
            ));

        $userGivenPath = trim(fgets(STDIN));

        // output a newline for aesthetic reasons
        $this->app['console.output']->write("\n");

        return $userGivenPath;
    }
}