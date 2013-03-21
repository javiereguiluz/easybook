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
        $slugsForMappedTransliteration = array(
            // English
            array(
                'The origin of Species',
                'the-origin-of-species'
            ),
            array(
                'Effects of Habit',
                'effects-of-habit'
            ),
            array(
                'Character of Domestic Varieties',
                'character-of-domestic-varieties'
            ),
            array(
                'Principle of Selection anciently followed, its Effects',
                'principle-of-selection-anciently-followed-its-effects'
            ),
            array(
                'Circumstances favourable to Man\'s power of Selection',
                'circumstances-favourable-to-mans-power-of-selection'
            ),
            // Spanish
            array(
                'El origen de las especies',
                'el-origen-de-las-especies'
            ),
            array(
                'Efectos de la costumbre y del uso y desuso de los órganos',
                'efectos-de-la-costumbre-y-del-uso-y-desuso-de-los-organos'
            ),
            array(
                'Caracteres de las variedades domésticas',
                'caracteres-de-las-variedades-domesticas'
            ),
            array(
                'Principios de selección seguidos de antiguo y sus efectos',
                'principios-de-seleccion-seguidos-de-antiguo-y-sus-efectos'
            ),
            array(
                'Circunstancias favorables al poder de selección del hombre',
                'circunstancias-favorables-al-poder-de-seleccion-del-hombre'
            ),
            // French
            array(
                'L’Origine des Espèces',
                'lorigine-des-especes'
            ),
            array(
                'Effets de l’usage ou du non-usage des parties',
                'effets-de-lusage-ou-du-non-usage-des-parties'
            ),
            array(
                'Caractères des variétés domestiques',
                'caracteres-des-varietes-domestiques'
            ),
            array(
                'La sélection appliquée depuis longtemps, ses effets',
                'la-selection-appliquee-depuis-longtemps-ses-effets'
            ),
            array(
                'Circonstances favorables à l’exercice de la sélection par l’homme',
                'circonstances-favorables-a-lexercice-de-la-selection-par-lhomme'
            ),
            // Greek
            array(
                'Η Καταγωγή των Ειδών είναι έργο του Άγγλου επιστήμονα',
                'h-katagwgh-twn-eidwn-einai-ergo-toy-aggloy-episthmona'
            ),
            array(
                'Σε ένα σύντομο ιστορικό σχεδίασμα των απόψεων περί της καταγωγής των ειδών',
                'se-ena-syntomo-istoriko-sxediasma-twn-apopsewn-peri-ths-katagwghs-twn-eidwn'
            ),
            // Russian
            array(
                'По крайней мере в поздних изданиях Дарвин',
                'po-krajnej-mere-v-pozdnih-izdaniyah-darvin'
            ),
            array(
                'такие как лошадь и осёл, или тигр и леопард являются видами',
                'takie-kak-loshad-i-osyol-ili-tigr-i-leopard-yavlyayutsya-vidami'
            ),
            // Japanese
            array(
                '彼は自然選択によって、生物は常に環境に適応するように変化し、',
                'e5bdbce381afe887aae784b6e981b8e68a9ee381abe38288e381a3e381a6e38081e7949fe789a9e381afe5b8b8e381abe792b0e5a283e381abe981a9e5bf9ce38199e3828be38288e38186e381abe5a489e58c96e38197e38081'
            ),
            array(
                '自然選択説につながる記録や考察は、ビーグル号の航海中（1831年-1836年）',
                'e887aae784b6e981b8e68a9ee8aaace381abe381a4e381aae3818ce3828be8a898e98cb2e38284e88083e5af9fe381afe38081e38393e383bce382b0e383abe58fb7e381aee888aae6b5b7e4b8adefbc881831e5b9b4-1836e5b9b4efbc89'
            ),
            // Edge cases
            array(
                'ŠŒŽšœžŸ¥µ ÀÁÂÃÄÅÆ ÇÈÉÊËÌÍÎÏÐ ÑÒÓÔÕÖØ ÙÚÛÜ Ýß àáâãäåæ çèéêëìíîïð ñòóôõöø ùúûüýÿ',
                'soezsoezyyenu-aaaaaaae-ceeeeiiiid-noooooo-uuuu-yss-aaaaaaae-ceeeeiiiid-noooooo-uuuuyy'
            ),
            array(
                '"·$%&/()=?¿¡!<>,;.:-_{}*+^',
                '-'
            ),
            array(
                '     ',
                '-'
            )
        );

        $slugsForNativeTransliteration = array(
            // English
            array(
                'The origin of Species',
                'the-origin-of-species'
            ),
            array(
                'Effects of Habit',
                'effects-of-habit'
            ),
            array(
                'Character of Domestic Varieties',
                'character-of-domestic-varieties'
            ),
            array(
                'Principle of Selection anciently followed, its Effects',
                'principle-of-selection-anciently-followed-its-effects'
            ),
            array(
                'Circumstances favourable to Man\'s power of Selection',
                'circumstances-favourable-to-mans-power-of-selection'
            ),
            // Spanish
            array(
                'El origen de las especies',
                'el-origen-de-las-especies'
            ),
            array(
                'Efectos de la costumbre y del uso y desuso de los órganos',
                'efectos-de-la-costumbre-y-del-uso-y-desuso-de-los-organos'
            ),
            array(
                'Caracteres de las variedades domésticas',
                'caracteres-de-las-variedades-domesticas'
            ),
            array(
                'Principios de selección seguidos de antiguo y sus efectos',
                'principios-de-seleccion-seguidos-de-antiguo-y-sus-efectos'
            ),
            array(
                'Circunstancias favorables al poder de selección del hombre',
                'circunstancias-favorables-al-poder-de-seleccion-del-hombre'
            ),
            // French
            array(
                'L’Origine des Espèces',
                'lorigine-des-especes'
            ),
            array(
                'Effets de l’usage ou du non-usage des parties',
                'effets-de-lusage-ou-du-non-usage-des-parties'
            ),
            array(
                'Caractères des variétés domestiques',
                'caracteres-des-varietes-domestiques'
            ),
            array(
                'La sélection appliquée depuis longtemps, ses effets',
                'la-selection-appliquee-depuis-longtemps-ses-effets'
            ),
            array(
                'Circonstances favorables à l’exercice de la sélection par l’homme',
                'circonstances-favorables-a-lexercice-de-la-selection-par-lhomme'
            ),
            // Greek
            array(
                'Η Καταγωγή των Ειδών είναι έργο του Άγγλου επιστήμονα',
                'e-katagoge-ton-eidon-einai-ergo-tou-anglou-epistemona'
            ),
            array(
                'Σε ένα σύντομο ιστορικό σχεδίασμα των απόψεων περί της καταγωγής των ειδών',
                'se-ena-syntomo-istoriko-schediasma-ton-apopseon-peri-tes-katagoges-ton-eidon'
            ),
            // Russian
            array(
                'По крайней мере в поздних изданиях Дарвин',
                'po-krajnej-mere-v-pozdnih-izdaniah-darvin'
            ),
            array(
                'такие как лошадь и осёл, или тигр и леопард являются видами',
                'takie-kak-losad-i-osel-ili-tigr-i-leopard-avlautsa-vidami'
            ),
            // Japanese
            array(
                '彼は自然選択によって、生物は常に環境に適応するように変化し、',
                'biha-zi-ran-xuan-zeniyotte-sheng-wuha-changni-huan-jingni-shi-yingsuruyouni-bian-huashi'
            ),
            array(
                '自然選択説につながる記録や考察は、ビーグル号の航海中（1831年-1836年）',
                'zi-ran-xuan-ze-shuonitsunagaru-ji-luya-kao-chahabiguru-haono-hang-hai-zhong-1831nian-1836nian'
            ),
            // Edge cases
            array(
                'ŠŒŽšœžŸ¥µ ÀÁÂÃÄÅÆ ÇÈÉÊËÌÍÎÏÐ ÑÒÓÔÕÖØ ÙÚÛÜ Ýß àáâãäåæ çèéêëìíîïð ñòóôõöø ùúûüýÿ',
                'szszy-aaaaaa-ceeeeiiii-nooooo-uuuu-y-aaaaaa-ceeeeiiii-nooooo-uuuuyy'
            ),
            array(
                '"·$%&/()=?¿¡!<>,;.:-_{}*+^',
                '-'
            ),
            array(
                '     ',
                '-'
            )
        );

        if (version_compare(phpversion(), '5.4.0', '>=') && extension_loaded('intl')) {
            return $slugsForNativeTransliteration;
        } else {
            return $slugsForMappedTransliteration;
        }
    }

    public function getStringsForMappedTransliteration()
    {
        return array(
            // latin
            array(
                'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖŐØÙÚÛÜŰÝÞßàáâãäåæçèéêëìíîïðñòóôõöőøùúûüűýþÿ',
                'AAAAAAAECEEEEIIIIDNOoOOOOOUUUUUYTHssaaaaaaaeceeeeiiiidnooooooouuuuuythy'
            ),
            // greek
            array(
                'ΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΠΡΣΤΥΦΧΨΩΆΈΊΌΎΉΏΪΫαβγδεζηθικλμνξοπρστυφχψωάέίόύήώςϊΰϋΐ',
                'ABGDEZH8IKLMN3OPRSTYFXPSWAEIOYHWIYabgdezh8iklmn3oprstyfxpswaeioyhwsiyyi'
            ),
            // turkish
            array(
                'ŞİÇÜÖĞşıçüöğ',
                'SICUOGsicuog'
            ),
            // russian
            array(
                'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдеёжзийклмнопрстуфхцчшщъыьэюя',
                'ABVGDEYoZhZIJKLMNOPRSTUFHCChShShYEYuYaabvgdeyozhzijklmnoprstufhcchshshyeyuya'
            ),
            // ukrainian
            array(
                'ЄІЇҐєіїґ',
                'YeIYiGyeiyig'
            ),
            // czech
            array(
                'ČĎĚŇŘŠŤŮŽčďěňřšťůž',
                'CDENRSTUZcdenrstuz'
            ),
            // polish
            array(
                'ĄĆĘŁŃÓŚŹŻąćęłńóśźż',
                'ACeLNoSZZacelnoszz'
            ),
            // latvian
            array(
                'ĀČĒĢĪĶĻŅŠŪŽāčēģīķļņšūž',
                'ACEGikLNSuZacegiklnsuz'
            ),
        );
    }

    public function getStringsForNativeTransliteration()
    {
        return array(
            // latin
            array(
                'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖŐØÙÚÛÜŰÝÞßàáâãäåæçèéêëìíîïðñòóôõöőøùúûüűýþÿ',
                'AAAAAAÆCEEEEIIIIÐNOOOOOOØUUUUUYÞßaaaaaaæceeeeiiiiðnooooooøuuuuuyþy'
            ),
            // greek
            array(
                'ΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΠΡΣΤΥΦΧΨΩΆΈΊΌΎΉΏΪΫαβγδεζηθικλμνξοπρστυφχψωάέίόύήώςϊΰϋΐ',
                'ABGDEZETHIKLMN\'XOPRSTYPHCHPSOAEIOYEOIYabgdezethiklmn\'xoprstyphchpsoaeioyeosiyyi'
            ),
            // turkish
            array(
                'ŞİÇÜÖĞşıçüöğ',
                'SICUOGsıcuog'
            ),
            // russian
            array(
                'АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдеёжзийклмнопрстуфхцчшщъыьэюя',
                'ABVGDEEZZIJKLMNOPRSTUFHCCSSʺYʹEUAabvgdeezzijklmnoprstufhccssʺyʹeua'
            ),
            // ukrainian
            array(
                'ЄІЇҐєіїґ',
                'EIIGeiig'
            ),
            // czech
            array(
                'ČĎĚŇŘŠŤŮŽčďěňřšťůž',
                'CDENRSTUZcdenrstuz'
            ),
            // polish
            array(
                'ĄĆĘŁŃÓŚŹŻąćęłńóśźż',
                'ACEŁNOSZZacełnoszz'
            ),
            // latvian
            array(
                'ĀČĒĢĪĶĻŅŠŪŽāčēģīķļņšūž',
                'ACEGIKLNSUZacegiklnsuz'
            ),
        );
    }
}