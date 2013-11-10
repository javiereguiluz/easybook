<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Util;

use Symfony\Component\Finder\Finder;
use Easybook\Util\Slugger;
use Easybook\Tests\TestCase;

class SluggerTest extends TestCase
{
    protected $slugger;

    public function setUp()
    {
        $configurationOptions = array('separator' => '-', 'prefix' => '');
        $this->slugger = new Slugger($configurationOptions);
    }

    /**
     * @dataProvider getSlugs
     */
    public function testSlugify($string, $expectedSlug)
    {
        $this->assertEquals($expectedSlug, $this->slugger->slugify($string));
    }

    /**
     * @dataProvider getStringsForMappedTransliteration
     */
    public function testMappedTransliteration($string, $expectedString)
    {
        $this->assertEquals($expectedString, $this->slugger->mappedTransliteration($string));
    }

    /**
     * @dataProvider getStringsForNativeTransliteration
     */
    public function testNativeTransliteration($string, $expectedString)
    {
        if (version_compare(phpversion(), '5.4.0', '<') || !extension_loaded('intl')) {
            $this->markTestSkipped(
              'Native transliteration is only available in PHP 5.4.0+ with the intl extension enabled'
            );
        }

        $this->assertEquals($expectedString, $this->slugger->nativeTransliteration($string));
    }

    public function getSlugs()
    {
        if (version_compare(phpversion(), '5.4.0', '>=') && extension_loaded('intl')) {
            $fixturesDir = __DIR__.'/fixtures/slugger/native';
        } else {
            $fixturesDir = __DIR__.'/fixtures/slugger/mapped';
        }

        return $this->loadFixtures($fixturesDir);
    }

    public function getStringsForMappedTransliteration()
    {
        return $this->loadFixtures(__DIR__.'/fixtures/transliterator/mapped');
    }

    public function getStringsForNativeTransliteration()
    {
        return $this->loadFixtures(__DIR__.'/fixtures/transliterator/native');
    }

    private function loadFixtures($fixturesDir)
    {
        $finder = new Finder();
        $stringFixtures = $finder->files()->name('strings.txt')->name('*.strings.txt')->in($fixturesDir);

        $fixtures = array();
        foreach ($stringFixtures as $filePath) {
            $strings = file($filePath, FILE_IGNORE_NEW_LINES);

            $slugsFilePath = str_replace('strings.', 'slugs.', $filePath);
            $slugs = file($slugsFilePath, FILE_IGNORE_NEW_LINES);

            foreach ($strings as $i => $string) {
                $fixtures[] = array($string, $slugs[$i]);
            }
        }

        return $fixtures;
    }
}