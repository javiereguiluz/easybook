<?php declare(strict_types=1);

namespace Easybook\Util;

use Iterator;
use Twig_Extension;
use Twig_SimpleFunction;

final class TwigCssExtension extends Twig_Extension
{
    public function getFunctions(): Iterator
    {
        yield new Twig_SimpleFunction('ligten', [$this, 'lighten']);
        yield new Twig_SimpleFunction('darken', [$this, 'darken']);
        yield new Twig_SimpleFunction('fade', [$this, 'fade']);
        yield new Twig_SimpleFunction('css_add', [$this, 'cssAdd']);
        yield new Twig_SimpleFunction('css_substract', [$this, 'cssSubstract']);
        yield new Twig_SimpleFunction('css_multiply', [$this, 'cssMultiply']);
        yield new Twig_SimpleFunction('css_divide', [$this, 'cssDivide']);
    }

    /*
     * Returns a color which is $percent lighter than $color
     *
     * @param string $color   The original color in hexadecimal format (eg. '#FFF', '#CC0000')
     * @param string|float $percent The percentage of "lightness" added to the original color
     *                        (eg. '10%', '50%', '66.66%'')
     *
     * @returns string The lightened color
     */
    public function lighten(string $color, $percent)
    {
        $percent = $this->normalizePercents($percent);

        $rgb = $this->hex2rgb($color);
        $hsl = $this->rgb2hsl($rgb);
        [$h, $s, $l] = $hsl;

        $l = min(1, max(0, $l + $percent));

        $rgb = $this->hsl2rgb([$h, $s, $l]);
        $color = $this->rgb2hex($rgb);

        return strtoupper($color);
    }

    /*
     * Returns a color which is $percent darker than $color
     *
     * @param string $color   The original color in hexadecimal format (eg. '#FFF', '#CC0000')
     * @param string $percent The percentage of "darkness" added to the original color
     *                        (eg. '10%', '50%', '66.66%'')
     *
     * @returns string The darkened color
     */
    public function darken(string $color, string $percent)
    {
        $percent = $this->normalizePercents($percent);

        return $this->lighten($color, -$percent);
    }

    /*
     * Changes the $opacity of the given $color.
     *
     * @param string $hex     The original color in hexadecimal format (eg. '#FFF', '#CC0000')
     * @param string $opacity The opacity of the result color (value ranges from 0.0 to 1.0)
     */
    public function fade($hex, $opacity)
    {
        $rgb = $this->hex2rgb($hex);

        return sprintf('rgba(%d, %d, %d, %.2f)', $rgb[0], $rgb[1], $rgb[2], max(0, min(1, $opacity)));
    }

    /*
     * Perfoms an addition with CSS lenght units
     * Examples: css_add('250px', 30) => returns '280px'
     *           css_add('8in', 12)   => returns '20in'
     */
    public function cssAdd($length, $factor)
    {
        return preg_replace_callback(
            '/(?<value>[\d\.]*)(?<unit>[a-z]{2})/i',
            function ($matches) use ($factor) {
                $unit = $matches['unit'] ?? 'px';

                return ($matches['value'] + $factor) . $unit;
            },
            $length
        );
    }

    /*
     * Perfoms a substraction with CSS lenght units
     * Examples: css_substract('250px', 50) => returns '200px'
     *           css_substract('8in', 2)   => returns '6in'
     */
    public function cssSubstract($length, $factor)
    {
        return preg_replace_callback(
            '/(?<value>[\d\.]*)(?<unit>[a-z]{2})/i',
            function ($matches) use ($factor) {
                $unit = $matches['unit'] ?? 'px';

                return ($matches['value'] - $factor) . $unit;
            },
            $length
        );
    }

    /*
     * Perfoms a multiplication with CSS lenght units
     * Examples: css_multiply('250px', 2) => returns '500px'
     *           css_multiply('8in', 4)   => returns '32in'
     */
    public function cssMultiply($length, $factor)
    {
        return preg_replace_callback(
            '/(?<value>[\d\.]*)(?<unit>[a-z]{2})/i',
            function ($matches) use ($factor) {
                $unit = $matches['unit'] ?? 'px';

                return ($matches['value'] * $factor) . $unit;
            },
            $length
        );
    }

    /*
     * Perfoms a division with CSS lenght units
     * Examples: css_divide('250px', 2) => returns '125px'
     *           css_divide('80in', 4)  => returns '20in'
     */
    public function cssDivide($length, $factor)
    {
        if ($factor === 0) {
            return 0;
        }

        return preg_replace_callback(
            '/(?<value>[\d\.]*)(?<unit>[a-z]{2})/i',
            function ($matches) use ($factor) {
                $unit = $matches['unit'] ?? 'px';

                if ((int) $factor === 0) {
                    return 0;
                }

                return ($matches['value'] / $factor) . $unit;
            },
            $length
        );
    }

    public function getName(): string
    {
        return self::class;
    }

    // -- Internal methods to convert between units ---------------------------

    /**
     * Transforms the given hexadecimal color string into an RGB array.
     */
    private function hex2rgb(string $hex): array
    {
        $hex = str_replace('#', '', $hex);

        // expand shorthand notation #36A -> #3366AA
        if (strlen($hex) === 3) {
            $hex = $hex{0}
            . $hex{0}
            . $hex{1}
            . $hex{1}
            . $hex{2}
            . $hex{2};
        }

        // expanded hex colors can only have 6 characters
        $hex = substr($hex, 0, 6);

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /**
     * Transforms the given RGB array into an hexadecimal color string.
     */
    private function rgb2hex(array $rgb): string
    {
        return sprintf('#%02s%02s%02s', dechex($rgb[0]), dechex($rgb[1]), dechex($rgb[2]));
    }

    /**
     * Transforms the given RGB color array into an HSL color array.
     * Code copied from Drupal CMS project.
     */
    private function rgb2hsl(array $rgb): array
    {
        [$r, $g, $b] = $rgb;
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $min = min($r, min($g, $b));
        $max = max($r, max($g, $b));
        $delta = $max - $min;

        $l = ($min + $max) / 2;
        $s = 0;

        if ($l > 0 && $l < 1) {
            $s = $delta / ($l < 0.5 ? (2 * $l) : (2 - 2 * $l));
        }

        $h = 0;

        if ($delta > 0) {
            if ($max === $r && $max !== $g) {
                $h += ($g - $b) / $delta;
            }
            if ($max === $g && $max !== $b) {
                $h += (2 + ($b - $r) / $delta);
            }
            if ($max === $b && $max !== $r) {
                $h += (4 + ($r - $g) / $delta);
            }
            $h /= 6;
        }

        return [$h, $s, $l];
    }

    /**
     * Transforms the given HSL color array into an RGB color array.
     * Code copied from Drupal CMS project.
     */
    private function hsl2rgb(array $hsl): array
    {
        [$h, $s, $l] = $hsl;

        $m2 = ($l <= 0.5) ? $l * ($s + 1) : $l + $s - $l * $s;
        $m1 = $l * 2 - $m2;

        $hue = function ($base) use ($m1, $m2) {
            $base = ($base < 0) ? $base + 1 : (($base > 1) ? $base - 1 : $base);
            if ($base * 6 < 1) {
                return $m1 + ($m2 - $m1) * $base * 6;
            }
            if ($base * 2 < 1) {
                return $m2;
            }
            if ($base * 3 < 2) {
                return $m1 + ($m2 - $m1) * (0.66666 - $base) * 6;
            }

            return $m1;
        };

        return [$hue($h + 0.33333) * 255, $hue($h) * 255, $hue($h - 0.33333) * 255];
    }

    /**
     * @param mixed $percent
     */
    private function normalizePercents($percent): float
    {
        if (is_float($percent)) {
            return $percent;
        }

        return $percent = str_replace('%', '', $percent) / 100;
    }
}
