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
    protected $app;

    public function setUp()
    {
        $this->app = new Application();
    }

    public function testSlugify()
    {
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

            $this->assertEquals($expectedSlug, $this->app->slugify($string));
        }
    }

    public function testSlugifyUniquely()
    {
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

            $this->assertEquals($expectedSlug, $this->app->slugifyUniquely($string));
        }
    }

}