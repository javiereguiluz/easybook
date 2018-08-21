<?php declare(strict_types=1);

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Util;

use GeSHi;

final class CodeHighlighter
{
    /**
     * @var GeSHi
     */
    private $geSHi;

    public function __construct(GeSHi $geSHi)
    {
        $this->geSHi = $geSHi;
    }

    /**
     * Highlights the given code according to the specified programming language.
     *
     * @param string $code     The source code to be highlighted
     * @param string $language The name of the programming language used in the code
     *
     * @return string The highlighted code
     */
    public function highlight(string $code, string $language): string
    {
        if ($language === 'html') {
            $language = 'html5';
        }

        // highlight the code using the best available highlighting library
        // (for now, easybook is limited to always using the GeSHi library)

        $this->geSHi->set_source($code);
        $this->geSHi->set_language($language);

        return $this->geSHi->parse_code();
    }
}
