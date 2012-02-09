<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Publishers;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Yaml\Yaml;
use Easybook\DependencyInjection\Application;
use Easybook\Console\ConsoleApplication;
use Easybook\Console\Command\BookPublishCommand;
use Easybook\Tests\TestCase;

class PublisherTest extends TestCase
{
    private $dir;
    private $app;
    
    public function setUp()
    {
        $this->app = new Application();
        $this->dir = sys_get_temp_dir().'/easybookTests';
        
        parent::setUp();
    }
    
    public function tearDown()
    {
        $this->app->get('filesystem')->remove($this->dir);
        
        parent::tearDown();
    }
    
    public function testBookPublish()
    {
        $console = new ConsoleApplication($this->app);
        
        // find the test books
        $books = $this->app->get('finder')
            ->directories()
            ->name('book*')
            ->depth(0)
            ->in(__DIR__.'/fixtures/')
        ;
        
        foreach ($books as $book) {
            $slug = $book->getFileName();
            
            // mirror test book contents in temp dir
            $this->app->get('filesystem')->mirror(
                __DIR__.'/fixtures/'.$slug.'/input',
                $this->dir.'/'.$slug
            );
            
            // look for and publish all the book editions
            $bookConfig = Yaml::parse($this->dir.'/'.$slug.'/config.yml');
            $editions = $bookConfig['book']['editions'];
            foreach ($editions as $editionName => $editionConfig) {
                // publish each book edition
                $input = new ArrayInput(array(
                    'command' => 'publish',
                    'slug'    => $slug,
                    'edition' => $editionName,
                    '--dir'   => $this->dir
                ));
                $console->find('publish')->run($input, new NullOutput());
                    
                // assert that generated files are exactly the same as expected
                $generatedFiles = $this->app->get('finder')
                    ->files()
                    ->notName('.gitignore')
                    ->in($this->dir.'/'.$slug.'/Output/'.$editionName)
                ;
                    
                foreach ($generatedFiles as $file) {
                    $this->assertFileEquals(
                        __DIR__.'/fixtures/'.$slug.'/expected/'.$editionName.'/'.$file->getRelativePathname(),
                        $file->getPathname(),
                        sprintf("'%s' file not properly generated", $file->getPathname())
                    );
                }
                
                // assert than book publication took less than 5 seconds
                $this->assertLessThan(
                    5,
                    $this->app['app.timer.finish'] - $this->app['app.timer.start'],
                    sprintf("Publication of '%s' edition for '%s' book took more than 5 seconds", $editionName, $slug)
                );
                
                // reset app state before the next publishing
                $this->app = new Application();
                $console = new ConsoleApplication($this->app);
            }
        }
    }
}
