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

use Easybook\Util\TwigCssExtension;
use Easybook\Tests\TestCase;

class TwigCssExtensionTest extends TestCase
{
    protected $extension;

    public function setUp()
    {
        $this->extension = new TwigCssExtension();
    }

    /**
     * @dataProvider getLightenColors
     */
    public function testLighten($originalColor, $percent, $expectedColor)
    {
        $this->assertEquals(
            $expectedColor,
            $this->extension->lighten($originalColor, $percent)
        );
    }

    public function getLightenColors()
    {
        return array(
            array('#FFF', '0%', '#FFFFFF'),
            array('#FFF', '50%', '#FFFFFF'),
            array('#FFF', '100%', '#FFFFFF'),
            array('#FFFFFF', '0%', '#FFFFFF'),
            array('#FFFFFF', '50%', '#FFFFFF'),
            array('#FFFFFF', '100%', '#FFFFFF'),
            array('#000', '100%', '#FFFFFF'),
            array('#000', '50%', '#7F7F7F'),
            array('#000', '0%', '#000000'),
            array('#000000', '100%', '#FFFFFF'),
            array('#000000', '50%', '#7F7F7F'),
            array('#000000', '0%', '#000000'),
            array('#C71585', '0%', '#C71584'), // rounding errors!
            array('#C71585', '33%', '#F390CE'),
            array('#C71585', '50%', '#FBDFF1'),
            array('#C71585', '66%', '#FFFFFF'),
            array('#C71585', '100%', '#FFFFFF'),
            // errors
            array('#FFF', '-100%', '#000000'),
            array('#FFF', '10000%', '#FFFFFF'),
            array('#', '100%', '#FFFFFF'),
            array('###', '100%', '#FFFFFF'),
        );
    }

    /**
     * @dataProvider getDarkenColors
     */
    public function testDarken($originalColor, $percent, $expectedColor)
    {
        $this->assertEquals(
            $expectedColor,
            $this->extension->darken($originalColor, $percent)
        );
    }

    public function getDarkenColors()
    {
        return array(
            array('#FFF', '0%', '#FFFFFF'),
            array('#FFF', '50%', '#7F7F7F'),
            array('#FFF', '100%', '#000000'),
            array('#FFFFFF', '0%', '#FFFFFF'),
            array('#FFFFFF', '50%', '#7F7F7F'),
            array('#FFFFFF', '100%', '#000000'),
            array('#000', '100%', '#000000'),
            array('#000', '50%', '#000000'),
            array('#000', '0%', '#000000'),
            array('#000000', '100%', '#000000'),
            array('#000000', '50%', '#000000'),
            array('#000000', '0%', '#000000'),
            array('#C71585', '0%', '#C71584'), // rounding errors!
            array('#C71585', '33%', '#2E041F'),
            array('#C71585', '50%', '#000000'),
            array('#C71585', '66%', '#000000'),
            array('#C71585', '100%', '#000000'),
            // errors
            array('#FFF', '-100%', '#FFFFFF'),
            array('#FFF', '10000%', '#000000'),
            array('#', '100%', '#000000'),
            array('###', '100%', '#000000'),
        );
    }

    /**
     * @dataProvider getFadeColors
     */
    public function testFade($originalColor, $opacity, $expectedColor)
    {
        $this->assertEquals(
            $expectedColor,
            $this->extension->fade($originalColor, $opacity)
        );
    }

    public function getFadeColors()
    {
        return array(
            array('#FFF', '0', 'rgba(255, 255, 255, 0.00)'),
            array('#FFF', '0.5', 'rgba(255, 255, 255, 0.50)'),
            array('#FFF', '1', 'rgba(255, 255, 255, 1.00)'),
            array('#FFFFFF', '0', 'rgba(255, 255, 255, 0.00)'),
            array('#FFFFFF', '0.5', 'rgba(255, 255, 255, 0.50)'),
            array('#FFFFFF', '1', 'rgba(255, 255, 255, 1.00)'),
            array('#000', '1', 'rgba(0, 0, 0, 1.00)'),
            array('#000', '0.5', 'rgba(0, 0, 0, 0.50)'),
            array('#000', '0', 'rgba(0, 0, 0, 0.00)'),
            array('#000000', '1', 'rgba(0, 0, 0, 1.00)'),
            array('#000000', '0.5', 'rgba(0, 0, 0, 0.50)'),
            array('#000000', '0', 'rgba(0, 0, 0, 0.00)'),
            array('#C71585', '0', 'rgba(199, 21, 133, 0.00)'),
            array('#C71585', '0.33', 'rgba(199, 21, 133, 0.33)'),
            array('#C71585', '0.5', 'rgba(199, 21, 133, 0.50)'),
            array('#C71585', '0.66', 'rgba(199, 21, 133, 0.66)'),
            array('#C71585', '1', 'rgba(199, 21, 133, 1.00)'),
            // errors
            array('#FFF', '-1', 'rgba(255, 255, 255, 0.00)'),
            array('#FFF', '10', 'rgba(255, 255, 255, 1.00)'),
            array('#', '1', 'rgba(0, 0, 0, 1.00)'),
            array('###', '1', 'rgba(0, 0, 0, 1.00)'),
        );
    }

    /**
     * @dataProvider getCssAddLengths
     */
    public function testCssAdd($length, $factor, $expectedLength)
    {
        $this->assertEquals(
            $expectedLength,
            $this->extension->css_add($length, $factor)
        );
    }

    public function getCssAddLengths()
    {
        return array(
            array('250px', '0', '250px'),
            array('250px', '50', '300px'),
            array('250px', '100', '350px'),
            array('0', '10', '0'),
            array('0', '50', '0'),
            array('0', '0', '0'),
            array('0px', '10', '10px'),
            array('0px', '50', '50px'),
            array('0px', '0', '0px'),
            array('250em', '0', '250em'),
            array('250em', '50', '300em'),
            array('250em', '100', '350em'),
            array('0em', '10', '10em'),
            array('0em', '50', '50em'),
            array('0em', '0', '0em'),
            array('2.5in', '0', '2.5in'),
            array('2.5in', '3.3', '5.8in'),
            array('2.5in', '5', '7.5in'),
            array('2.5in', '6.6', '9.1in'),
            array('2.5in', '10', '12.5in'),
        );
    }

    /**
     * @dataProvider getCssSubstractLengths
     */
    public function testCssSubstract($length, $factor, $expectedLength)
    {
        $this->assertEquals(
            $expectedLength,
            $this->extension->css_substract($length, $factor)
        );
    }

    public function getCssSubstractLengths()
    {
        return array(
            array('250px', '0', '250px'),
            array('250px', '50', '200px'),
            array('250px', '100', '150px'),
            array('0', '10', '0'),
            array('0', '50', '0'),
            array('0', '0', '0'),
            array('0px', '10', '-10px'),
            array('0px', '50', '-50px'),
            array('0px', '0', '0px'),
            array('250em', '0', '250em'),
            array('250em', '50', '200em'),
            array('250em', '100', '150em'),
            array('0em', '10', '-10em'),
            array('0em', '50', '-50em'),
            array('0em', '0', '0em'),
            array('2.5in', '0', '2.5in'),
            array('2.5in', '3.3', '-0.8in'),
            array('2.5in', '5', '-2.5in'),
            array('2.5in', '6.6', '-4.1in'),
            array('2.5in', '10', '-7.5in'),
        );
    }

    /**
     * @dataProvider getCssMultiplyLengths
     */
    public function testCssMultiply($length, $factor, $expectedLength)
    {
        $this->assertEquals(
            $expectedLength,
            $this->extension->css_multiply($length, $factor)
        );
    }

    public function getCssMultiplyLengths()
    {
        return array(
            array('250px', '0', '0px'),
            array('250px', '5', '1250px'),
            array('250px', '10', '2500px'),
            array('0', '10', '0'),
            array('0', '50', '0'),
            array('0', '0', '0'),
            array('0px', '10', '0px'),
            array('0px', '50', '0px'),
            array('0px', '0', '0px'),
            array('250em', '0', '0em'),
            array('250em', '5', '1250em'),
            array('250em', '10', '2500em'),
            array('0em', '10', '0em'),
            array('0em', '50', '0em'),
            array('0em', '0', '0em'),
            array('2.5in', '0', '0in'),
            array('2.5in', '3.3', '8.25in'),
            array('2.5in', '5', '12.5in'),
            array('2.5in', '6.6', '16.5in'),
            array('2.5in', '10', '25in'),
        );
    }

    /**
     * @dataProvider getCssDivideLengths
     */
    public function testCssDivide($length, $factor, $expectedLength)
    {
        $this->assertEquals(
            $expectedLength,
            $this->extension->css_divide($length, $factor)
        );
    }

    public function getCssDivideLengths()
    {
        return array(
            array('250px', '0', '0'),
            array('250px', '5', '50px'),
            array('250px', '10', '25px'),
            array('0', '10', '0'),
            array('0', '50', '0'),
            array('0', '0', '0'),
            array('0px', '10', '0px'),
            array('0px', '50', '0px'),
            array('0px', '0', '0'),
            array('250em', '0', '0'),
            array('250em', '5', '50em'),
            array('250em', '10', '25em'),
            array('0em', '10', '0em'),
            array('0em', '50', '0em'),
            array('0em', '0', '0'),
            array('2.5in', '0', '0'),
            array('2.5in', '3.3', '0.75757575757576in'),
            array('2.5in', '5', '0.5in'),
            array('2.5in', '6.6', '0.37878787878788in'),
            array('2.5in', '10', '0.25in'),
        );
    }
}