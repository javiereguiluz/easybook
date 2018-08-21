<?php declare(strict_types=1);

namespace Easybook\Tests\Util;

use Easybook\Tests\AbstractContainerAwareTestCase;
use Easybook\Util\TwigCssExtension;
use Iterator;

final class TwigCssExtensionTest extends AbstractContainerAwareTestCase
{
    /**
     * @var TwigCssExtension
     */
    private $twigCssExtension;

    protected function setUp(): void
    {
        $this->twigCssExtension = $this->container->get(TwigCssExtension::class);
    }

    /**
     * @dataProvider getLightenColors()
     */
    public function testLighten(string $originalColor, string $percent, string $expectedColor): void
    {
        $this->assertSame($expectedColor, $this->twigCssExtension->lighten($originalColor, $percent));
    }

    public function getLightenColors(): Iterator
    {
        yield ['#FFF', '0%', '#FFFFFF'];
        yield ['#FFF', '50%', '#FFFFFF'];
        yield ['#FFF', '100%', '#FFFFFF'];
        yield ['#FFFFFF', '0%', '#FFFFFF'];
        yield ['#FFFFFF', '50%', '#FFFFFF'];
        yield ['#FFFFFF', '100%', '#FFFFFF'];
        yield ['#000', '100%', '#FFFFFF'];
        yield ['#000', '50%', '#7F7F7F'];
        yield ['#000', '0%', '#000000'];
        yield ['#000000', '100%', '#FFFFFF'];
        yield ['#000000', '50%', '#7F7F7F'];
        yield ['#000000', '0%', '#000000'];
        yield ['#C71585', '0%', '#C71584']; // rounding error
        yield ['#C71585', '33%', '#F390CE'];
        yield ['#C71585', '50%', '#FBDFF1'];
        yield ['#C71585', '66%', '#FFFFFF'];
        yield ['#C71585', '100%', '#FFFFFF'];
        // errors
        yield ['#FFF', '-100%', '#000000'];
        yield ['#FFF', '10000%', '#FFFFFF'];
        yield ['#', '100%', '#FFFFFF'];
        yield ['###', '100%', '#FFFFFF'];
    }

    /**
     * @dataProvider getDarkenColors()
     */
    public function testDarken(string $originalColor, string $percent, string $expectedColor): void
    {
        $this->assertSame($expectedColor, $this->twigCssExtension->darken($originalColor, $percent));
    }

    public function getDarkenColors(): Iterator
    {
        yield ['#FFF', '0%', '#FFFFFF'];
        yield ['#FFF', '50%', '#7F7F7F'];
        yield ['#FFF', '100%', '#000000'];
        yield ['#FFFFFF', '0%', '#FFFFFF'];
        yield ['#FFFFFF', '50%', '#7F7F7F'];
        yield ['#FFFFFF', '100%', '#000000'];
        yield ['#000', '100%', '#000000'];
        yield ['#000', '50%', '#000000'];
        yield ['#000', '0%', '#000000'];
        yield ['#000000', '100%', '#000000'];
        yield ['#000000', '50%', '#000000'];
        yield ['#000000', '0%', '#000000'];
        yield ['#C71585', '0%', '#C71584']; // rounding error
        yield ['#C71585', '33%', '#2E041F'];
        yield ['#C71585', '50%', '#000000'];
        yield ['#C71585', '66%', '#000000'];
        yield ['#C71585', '100%', '#000000'];
        // errors
        yield ['#FFF', '-100%', '#FFFFFF'];
        yield ['#FFF', '10000%', '#000000'];
        yield ['#', '100%', '#000000'];
        yield ['###', '100%', '#000000'];
    }

    /**
     * @dataProvider getFadeColors()
     */
    public function testFade(string $originalColor, string $opacity, string $expectedColor): void
    {
        $this->assertSame($expectedColor, $this->twigCssExtension->fade($originalColor, $opacity));
    }

    public function getFadeColors(): Iterator
    {
        yield ['#FFF', '0', 'rgba(255, 255, 255, 0.00)'];
        yield ['#FFF', '0.5', 'rgba(255, 255, 255, 0.50)'];
        yield ['#FFF', '1', 'rgba(255, 255, 255, 1.00)'];
        yield ['#FFFFFF', '0', 'rgba(255, 255, 255, 0.00)'];
        yield ['#FFFFFF', '0.5', 'rgba(255, 255, 255, 0.50)'];
        yield ['#FFFFFF', '1', 'rgba(255, 255, 255, 1.00)'];
        yield ['#000', '1', 'rgba(0, 0, 0, 1.00)'];
        yield ['#000', '0.5', 'rgba(0, 0, 0, 0.50)'];
        yield ['#000', '0', 'rgba(0, 0, 0, 0.00)'];
        yield ['#000000', '1', 'rgba(0, 0, 0, 1.00)'];
        yield ['#000000', '0.5', 'rgba(0, 0, 0, 0.50)'];
        yield ['#000000', '0', 'rgba(0, 0, 0, 0.00)'];
        yield ['#C71585', '0', 'rgba(199, 21, 133, 0.00)'];
        yield ['#C71585', '0.33', 'rgba(199, 21, 133, 0.33)'];
        yield ['#C71585', '0.5', 'rgba(199, 21, 133, 0.50)'];
        yield ['#C71585', '0.66', 'rgba(199, 21, 133, 0.66)'];
        yield ['#C71585', '1', 'rgba(199, 21, 133, 1.00)'];
        // errors
        yield ['#FFF', '-1', 'rgba(255, 255, 255, 0.00)'];
        yield ['#FFF', '10', 'rgba(255, 255, 255, 1.00)'];
        yield ['#', '1', 'rgba(0, 0, 0, 1.00)'];
        yield ['###', '1', 'rgba(0, 0, 0, 1.00)'];
    }

    /**
     * @dataProvider getCssAddLengths()
     */
    public function testCssAdd(string $length, string $factor, string $expectedLength): void
    {
        $this->assertSame($expectedLength, $this->twigCssExtension->cssAdd($length, $factor));
    }

    public function getCssAddLengths(): Iterator
    {
        yield ['250px', '0', '250px'];
        yield ['250px', '50', '300px'];
        yield ['250px', '100', '350px'];
        yield ['0', '10', '0'];
        yield ['0', '50', '0'];
        yield ['0', '0', '0'];
        yield ['0px', '10', '10px'];
        yield ['0px', '50', '50px'];
        yield ['0px', '0', '0px'];
        yield ['250em', '0', '250em'];
        yield ['250em', '50', '300em'];
        yield ['250em', '100', '350em'];
        yield ['0em', '10', '10em'];
        yield ['0em', '50', '50em'];
        yield ['0em', '0', '0em'];
        yield ['2.5in', '0', '2.5in'];
        yield ['2.5in', '3.3', '5.8in'];
        yield ['2.5in', '5', '7.5in'];
        yield ['2.5in', '6.6', '9.1in'];
        yield ['2.5in', '10', '12.5in'];
    }

    /**
     * @dataProvider getCssSubstractLengths
     */
    public function testCssSubstract(string $length, string $factor, string $expectedLength): void
    {
        $this->assertSame($expectedLength, $this->twigCssExtension->cssSubstract($length, $factor));
    }

    public function getCssSubstractLengths(): Iterator
    {
        yield ['250px', '0', '250px'];
        yield ['250px', '50', '200px'];
        yield ['250px', '100', '150px'];
        yield ['0', '10', '0'];
        yield ['0', '50', '0'];
        yield ['0', '0', '0'];
        yield ['0px', '10', '-10px'];
        yield ['0px', '50', '-50px'];
        yield ['0px', '0', '0px'];
        yield ['250em', '0', '250em'];
        yield ['250em', '50', '200em'];
        yield ['250em', '100', '150em'];
        yield ['0em', '10', '-10em'];
        yield ['0em', '50', '-50em'];
        yield ['0em', '0', '0em'];
        yield ['2.5in', '0', '2.5in'];
        yield ['2.5in', '3.3', '-0.8in'];
        yield ['2.5in', '5', '-2.5in'];
        yield ['2.5in', '6.6', '-4.1in'];
        yield ['2.5in', '10', '-7.5in'];
    }

    /**
     * @dataProvider getCssMultiplyLengths()
     */
    public function testCssMultiply(string $length, string $factor, string $expectedLength): void
    {
        $this->assertSame($expectedLength, $this->twigCssExtension->cssMultiply($length, $factor));
    }

    public function getCssMultiplyLengths(): Iterator
    {
        yield ['250px', '0', '0px'];
        yield ['250px', '5', '1250px'];
        yield ['250px', '10', '2500px'];
        yield ['0', '10', '0'];
        yield ['0', '50', '0'];
        yield ['0', '0', '0'];
        yield ['0px', '10', '0px'];
        yield ['0px', '50', '0px'];
        yield ['0px', '0', '0px'];
        yield ['250em', '0', '0em'];
        yield ['250em', '5', '1250em'];
        yield ['250em', '10', '2500em'];
        yield ['0em', '10', '0em'];
        yield ['0em', '50', '0em'];
        yield ['0em', '0', '0em'];
        yield ['2.5in', '0', '0in'];
        yield ['2.5in', '3.3', '8.25in'];
        yield ['2.5in', '5', '12.5in'];
        yield ['2.5in', '6.6', '16.5in'];
        yield ['2.5in', '10', '25in'];
    }

    /**
     * @dataProvider getCssDivideLengths()
     */
    public function testCssDivide(string $length, string $factor, string $expectedLength): void
    {
        $this->assertSame($expectedLength, $this->twigCssExtension->cssDivide($length, $factor));
    }

    public function getCssDivideLengths(): Iterator
    {
        yield ['250px', '0', '0'];
        yield ['250px', '5', '50px'];
        yield ['250px', '10', '25px'];
        yield ['0', '10', '0'];
        yield ['0', '50', '0'];
        yield ['0', '0', '0'];
        yield ['0px', '10', '0px'];
        yield ['0px', '50', '0px'];
        yield ['0px', '0', '0'];
        yield ['250em', '0', '0'];
        yield ['250em', '5', '50em'];
        yield ['250em', '10', '25em'];
        yield ['0em', '10', '0em'];
        yield ['0em', '50', '0em'];
        yield ['0em', '0', '0'];
        yield ['2.5in', '0', '0'];
        yield ['2.5in', '3.3', '0.75757575757576in'];
        yield ['2.5in', '5', '0.5in'];
        yield ['2.5in', '6.6', '0.37878787878788in'];
        yield ['2.5in', '10', '0.25in'];
    }
}
