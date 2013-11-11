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

class TwigCssExtension extends \Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'lighten'       => new \Twig_Function_Method($this, 'lighten'),
            'darken'        => new \Twig_Function_Method($this, 'darken'),
            'fade'          => new \Twig_Function_Method($this, 'fade'),
            'css_add'       => new \Twig_Function_Method($this, 'css_add'),
            'css_substract' => new \Twig_Function_Method($this, 'css_substract'),
            'css_multiply'  => new \Twig_Function_Method($this, 'css_multiply'),
            'css_divide'    => new \Twig_Function_Method($this, 'css_divide'),

        );
    }

    /*
     * Returns a color which is $percent lighter than $color
     *
     * @param string $color   The original color in hexadecimal format (eg. '#FFF', '#CC0000')
     * @param string $percent The percentage of "lightness" added to the original color
     *                        (eg. '10%', '50%', '66.66%'')
     *
     * @returns string The lightened color
     */
    public function lighten($color, $percent)
    {
        $percent = str_replace('%', '', $percent) / 100;

        $rgb = $this->hex2rgb($color);
        $hsl = $this->rgb2hsl($rgb);
        list($h, $s, $l) = $hsl;

        $l = min(1, max(0, $l + $percent));

        $rgb = $this->hsl2rgb(array($h, $s, $l));
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
    public function darken($color, $percent)
    {
        return $this->lighten($color, -$percent);
    }

    /*
     * Changes the $opacity of the given $color.
     *
     * @param string $hex     The original color in hexadecimal format (eg. '#FFF', '#CC0000')
     * @param string $opacity The opacity of the result color (value ranges from 0.0 to 1.0)
     *
     */
    public function fade($hex, $opacity)
    {
        $rgb = $this->hex2rgb($hex);

        return sprintf('rgba(%d, %d, %d, %.2f)',
            $rgb[0], $rgb[1], $rgb[2], max(0, min(1, $opacity))
        );
    }

    /*
     * Perfoms an addition with CSS lenght units
     * Examples: css_add('250px', 30) => returns '280px'
     *           css_add('8in', 12)   => returns '20in'
     */
    public function css_add($length, $factor)
    {
        return preg_replace_callback(
            '/(?<value>[\d\.]*)(?<unit>[a-z]{2})/i',
            function($matches) use ($factor) {
                $unit = isset($matches['unit']) ? $matches['unit'] : 'px';

                return ($matches['value'] + $factor).$unit;
            },
            $length
        );
    }

    /*
     * Perfoms a substraction with CSS lenght units
     * Examples: css_substract('250px', 50) => returns '200px'
     *           css_substract('8in', 2)   => returns '6in'
     */
    public function css_substract($length, $factor)
    {
        return preg_replace_callback(
            '/(?<value>[\d\.]*)(?<unit>[a-z]{2})/i',
            function($matches) use ($factor) {
                $unit = isset($matches['unit']) ? $matches['unit'] : 'px';

                return ($matches['value'] - $factor).$unit;
            },
            $length
        );
    }

    /*
     * Perfoms a multiplication with CSS lenght units
     * Examples: css_multiply('250px', 2) => returns '500px'
     *           css_multiply('8in', 4)   => returns '32in'
     */
    public function css_multiply($length, $factor)
    {
        return preg_replace_callback(
            '/(?<value>[\d\.]*)(?<unit>[a-z]{2})/i',
            function($matches) use ($factor) {
                $unit = isset($matches['unit']) ? $matches['unit'] : 'px';

                return ($matches['value'] * $factor).$unit;
            },
            $length
        );
    }

    /*
     * Perfoms a division with CSS lenght units
     * Examples: css_divide('250px', 2) => returns '125px'
     *           css_divide('80in', 4)  => returns '20in'
     */
    public function css_divide($length, $factor)
    {
        if (0 == $factor) {
            return 0;
        }

        return preg_replace_callback(
            '/(?<value>[\d\.]*)(?<unit>[a-z]{2})/i',
            function($matches) use ($factor) {
                $unit = isset($matches['unit']) ? $matches['unit'] : 'px';

                return ($matches['value'] / $factor).$unit;
            },
            $length
        );
    }

    // -- Internal methods to convert between units ---------------------------

    private function hex2rgb($hex)
    {
        $hex = str_replace('#', '', $hex);

        // expand shorthand notation #36A -> #3366AA
        if (3 == strlen($hex)) {
            $hex = $hex{0}.$hex{0}.$hex{1}.$hex{1}.$hex{2}.$hex{2};
        }

        // expanded hex colors can only have 6 characters
        $hex = substr($hex, 0, 6);

        return array(
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        );
    }

    private function rgb2hex($rgb)
    {
        return sprintf('#%02s%02s%02s', dechex($rgb[0]), dechex($rgb[1]), dechex($rgb[2]));
    }

    /* code copied from Drupal CMS */
    private function rgb2hsl($rgb)
    {
        list($r, $g, $b) = $rgb;
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
            if ($max == $r && $max != $g) { $h += ($g - $b) / $delta;       }
            if ($max == $g && $max != $b) { $h += (2 + ($b - $r) / $delta); }
            if ($max == $b && $max != $r) { $h += (4 + ($r - $g) / $delta); }
            $h /= 6;
        }

        return array($h, $s, $l);
    }

    /* code copied from Drupal CMS */
    private function hsl2rgb($hsl)
    {
        list($h, $s, $l) = $hsl;

        $m2 = ($l <= 0.5) ? $l * ( $s + 1 ) : $l + $s - $l * $s;
        $m1 = $l * 2 - $m2;

        $hue = function ($base) use ($m1, $m2) {
            $base = ($base < 0) ? $base + 1 : ( ($base > 1) ? $base - 1 : $base );
            if ($base * 6 < 1) { return $m1 + ($m2 - $m1) * $base * 6; }
            if ($base * 2 < 1) { return $m2; }
            if ($base * 3 < 2) { return $m1 + ($m2 - $m1) * (0.66666 - $base) * 6; }

            return $m1;
        };

        return array(
            $hue($h + 0.33333) * 255,
            $hue($h) * 255,
            $hue($h - 0.33333) * 255
        );
    }

    public function getName()
    {
        return 'twig_css_extension';
    }
}
