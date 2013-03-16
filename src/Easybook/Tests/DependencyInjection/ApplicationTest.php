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

class ApplicationTest extends TestCase
{
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

    /**
     * @expectedException RuntimeException
     */
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

        $prince = $app->get('prince');
    }

    public function testSlugify()
    {
        $app = new Application();

        // don't use a dataProvider because it interferes with the slug generation
        $slugs = array(
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet'),
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet'),
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam'),
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet'),
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam'),
        );

        foreach ($slugs as $slug) {
            $string = $slug[0];
            $expectedSlug = $slug[1];

            $this->assertEquals($expectedSlug, $app->slugify($string));
        }
    }

    public function testSlugifyUniquely()
    {
        $app = new Application();

        // don't use a dataProvider because it interferes with the slug generation
        $uniqueSlugs = array(
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet'),
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet-2'),
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet-3'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam-2'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam-3'),
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet-4'),
            array('Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet-5'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam-4'),
            array('Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam-5'),
        );

        foreach ($uniqueSlugs as $slug) {
            $string = $slug[0];
            $expectedSlug = $slug[1];

            $this->assertEquals($expectedSlug, $app->slugifyUniquely($string));
        }
    }

}