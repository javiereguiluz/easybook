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

class Slugger
{
    private $separator;
    private $prefix;

    /**
     * @param array $options The global configuration options for the slugger.
     *                       These options can be overriden in the 'slugify()' call
     */
    public function __construct($options)
    {
        $this->separator = $options['separator'];
        $this->prefix    = $options['prefix'];
    }

    /**
     * Transforms the original string into a web-safe slug.
     * Code adapted from http://cubiq.org/the-perfect-php-clean-url-generator
     *
     * @param  string  $string    The string to slug
     * @param  string  $separator Used between words and to replace illegal characters
     * @param  string  $prefix    Prefix to be appended at the beginning of the slug
     * @return string             The generated slug
     */
    public function slugify($string, $separator = null, $prefix = null)
    {
        $separator = $separator ?: $this->separator;
        $prefix    = $prefix    ?: $this->prefix;

        $string = trim(strip_tags($string));

        if (version_compare(phpversion(), '5.4.0', '>=') && extension_loaded('intl')) {
            $slug = $this->nativeTransliteration($string);
        } else {
            $slug = $this->mappedTransliteration($string);
        }

        $slug = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $slug);
        $slug = strtolower(trim($slug, $separator));
        $slug = preg_replace("/[\/_|+ -]+/", $separator, $slug);

        $slug = $prefix.$slug;

        return $slug;
    }

    /**
     * Transliterates strings using the PHP 5.4.0+ built-in transliterator.
     *
     * @param  string $string The string to transliterate
     *
     * @return string        The transliterated string
     */
    public function nativeTransliteration($string)
    {
        return transliterator_transliterate("Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC;", $string);
    }

    /**
     * Transliterates strings using a map of characters, for systems where
     * the PHP 5.4.0+ built-in transliterator isn't available.
     *
     * @param  string $string The string to transliterate
     *
     * @return string        The transliterated string
     */
    public function mappedTransliteration($string)
    {
        $string = $this->mappedTransliterator($string);

        // consider Japanese as an special case
        if ($this->isJapanese($string)) {
            return $this->utf8_uri_encode($string);
        }

        if (function_exists('iconv')) {
            return iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        } elseif (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($string, 'ASCII');
        } else {
            // if both iconv and mb_* functions are unavailable, use a
            // simple method to remove accents
            return strtr(
                utf8_decode($string),
                utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
                'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy'
            );
        }
    }


    /**
     * Transliterates a string using some pre-configured character maps.
     */
    private function mappedTransliterator($string)
    {
        // transliterator mapping copied from:
        // http://iamseanmurphy.com/creating-seo-friendly-urls-in-php-with-url-slug/
        $transliterator = array(
            // Latin
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
            'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
            'ß' => 'ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
            'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
            'ÿ' => 'y',

            // Symbols
            '©' => '(c)', '¥' => '', '€' => '',

            // Greek
            'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
            'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
            'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
            'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
            'Ϋ' => 'Y',
            'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
            'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
            'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
            'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
            'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',

            // Turkish
            'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U', 'Ö' => 'O', 'Ğ' => 'G',
            'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ğ' => 'g',

            // Russian
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
            'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
            'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
            'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
            'Я' => 'Ya',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
            'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
            'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
            'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
            'я' => 'ya',

            // Ukrainian
            'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
            'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',

            // Czech
            'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
            'Ž' => 'Z',
            'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
            'ž' => 'z',

            // Polish
            'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z',
            'Ż' => 'Z',
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
            'ż' => 'z',

            // Latvian
            'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
            'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
            'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
            'š' => 's', 'ū' => 'u', 'ž' => 'z'
        );

        $string = str_replace(array_keys($transliterator), $transliterator, $string);

        return $string;
    }

    /**
     * Detects if the given string contains at least one character written in
     * Japanese.
     *
     * @param  string  $string The string to consider
     * @return boolean         It returns 'true' if the string contains Japanese
     *                         characters and false otherwise.
     */
    private function isJapanese ($string)
    {
        return preg_match('/[\x{4E00}-\x{9FBF}\x{3040}-\x{309F}\x{30A0}-\x{30FF}]/u', $string);
    }

    /**
     * Encode the Unicode values to be used in the URI.
     *
     * @param string $utf8_string
     * @param int $length Max length of the string
     * @return string String with Unicode encoded for URI.
     *
     * code copied from WordPress (wp-includes/formatting.php)
     */
    private function utf8_uri_encode( $utf8_string, $length = 0 ) {
        $unicode = '';
        $values = array();
        $num_octets = 1;
        $unicode_length = 0;

        $string_length = strlen( $utf8_string );
        for ($i = 0; $i < $string_length; $i++ ) {

            $value = ord( $utf8_string[ $i ] );

            if ( $value < 128 ) {
                if ( $length && ( $unicode_length >= $length ) )
                    break;
                $unicode .= chr($value);
                $unicode_length++;
            } else {
                if ( count( $values ) == 0 ) $num_octets = ( $value < 224 ) ? 2 : 3;

                $values[] = $value;

                if ( $length && ( $unicode_length + ($num_octets * 3) ) > $length )
                    break;
                if ( count( $values ) == $num_octets ) {
                    if ($num_octets == 3) {
                        $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
                        $unicode_length += 9;
                    } else {
                        $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
                        $unicode_length += 6;
                    }

                    $values = array();
                    $num_octets = 1;
                }
            }
        }

        return $unicode;
    }
}
