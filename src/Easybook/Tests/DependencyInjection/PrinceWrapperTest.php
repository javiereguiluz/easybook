<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\DependencyInjection;

use Easybook\Tests\TestCase;
use Easybook\DependencyInjection\Application;

/**
 * Tests related to the PrinceXML PHP Wrapper used by the
 * Easybook\DependencyInjection\Application class
 */
class PrinceWrapperTest extends TestCase
{
    public function testFindPrinceXmlExecutableWithUnkownPaths()
    {
        $app = new Application();
        $app->set('prince.default_paths', array(
            uniqid('this_path_does_not_exist_1_'),
            uniqid('this_path_does_not_exist_2_'),
            uniqid('this_path_does_not_exist_3_'),
        ));

        $this->assertNull($app->findPrinceXmlExecutable());
    }

    public function testFindPrinceXmlExecutableWithOneKnownPath()
    {
        $app = new Application();
        $app->set('prince.default_paths', array(
            uniqid('this_path_does_not_exist_1_'),
            $app->get('app.dir.base'),
            uniqid('this_path_does_not_exist_3_'),
        ));

        $this->assertEquals($app->get('app.dir.base'), $app->findPrinceXmlExecutable());
    }

    public function testFindPrinceXmlExecutableWithSeveralKnownPath()
    {
        $app = new Application();
        $app->set('prince.default_paths', array(
            uniqid('this_path_does_not_exist_1_'),
            $app->get('app.dir.base'),
            uniqid('this_path_does_not_exist_3_'),
            $app->get('app.dir.cache'),
        ));

        $this->assertEquals($app->get('app.dir.base'), $app->findPrinceXmlExecutable());
    }

    public function testFindPrinceXmlExecutableWithConfiguredPath()
    {
        $app = new Application();

        $app->set('prince.path', '/foo');
        $prince = $app->get('prince');

        $this->assertEquals('/foo', $prince->getExePath());
    }

    public function testFindPrinceXmlExecutableWithGuessedPath()
    {
        $app = $this->getMock('Easybook\DependencyInjection\Application', array('findPrinceXmlExecutable'));

        $app->expects($this->any())
            ->method('findPrinceXmlExecutable')
            ->will($this->returnValue('/path/to/the/price/executable'));

        $prince = $app->get('prince');

        $this->assertEquals('/path/to/the/price/executable', $prince->getExePath());
    }

    public function testFindPrinceXmlExecutableWithInexistentPath()
    {
        $app = $this->getMock('Easybook\DependencyInjection\Application',
            array('findPrinceXmlExecutable', 'askForPrinceXMLExecutablePath'));

        $app->expects($this->any())
            ->method('findPrinceXmlExecutable')
            ->will($this->returnValue(null));

        $app->expects($this->any())
            ->method('askForPrinceXMLExecutablePath')
            ->will($this->returnValue(uniqid('this-path-does-not-exist')));

        try {
            $prince = $app->get('prince');
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf('RuntimeException', $e);
            $this->assertContains('We couldn\'t find the PrinceXML executable', $e->getMessage());
        }
    }

    public function testPrinceXMLInstanceObtained()
    {
        $app = new Application();
        $app->set('prince.default_paths', array($app->get('app.dir.cache')));

        $prince = $app->get('prince');

        $this->assertInstanceOf('Easybook\Util\Prince', $prince);
    }
}