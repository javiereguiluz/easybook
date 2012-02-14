<?php
/*************************************************************************************
 * twig.php
 * ----------
 * Author: Javier Eguiluz
 * Copyright: (c) 2011 Javier Eguiluz
 * Release Version: 1.0.7.20
 * Date Started: 2011/30/10
 *
 * Twig template language file for GeSHi.
 *
 *
 *************************************************************************************
 *
 *     This file is part of GeSHi.
 *
 *   GeSHi is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 *   GeSHi is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with GeSHi; if not, write to the Free Software
 *   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 ************************************************************************************/

$language_data = array (
    'LANG_NAME' => 'Twig',
    'COMMENT_SINGLE' => array(),
    'COMMENT_MULTI' => array('{#' => '#}'),
    'CASE_KEYWORDS' => GESHI_CAPS_NO_CHANGE,
    'QUOTEMARKS' => array("'", '"'),
    'ESCAPE_CHAR' => '\\',
    'KEYWORDS' => array(
        0 => array(
            '{{', '}}', '{%', '%}'
            ),
        1 => array(
            'autoescape', 'endautoescape', 'block', 'endblock', 'extends', 'filter', 'endfilter', 'for', 'endfor', 'from', 'if', 'else', 'elseif', 'endif', 'import', 'include', 'macro', 'raw', 'endraw', 'set', 'spaceless', 'endspaceless', 'use', 'render', 'trans', 'endtrans'
            ),
        2 => array(
            'capitalize', 'convert_encoding', 'date', 'default', 'escape', 'format', 'join', 'json_encode', 'keys', 'length', 'lower', 'merge', 'raw', 'replace', 'reverse', 'sort', 'striptags', 'title', 'upper', 'url_encode'
            ),
        3 => array(
            'attribute', 'block', 'constant', 'cycle', 'parent', 'range'
            ),
        4 => array(
            ),
        5 => array(
            ),
        6 => array(
             'constant', 'defined', 'divisibleby', 'empty', 'even', 'none', 'odd', 'sameas', 'true', 'false'
            ),
        7 => array(
            ),
        ),
    'SYMBOLS' => array(
        '/', '=', '==', '!=', '>', '<', '>=', '<=', '!', '%'
        ),
    'CASE_SENSITIVE' => array(
        GESHI_COMMENTS => false,
        1 => false,
        2 => false,
        3 => false,
        4 => false,
        5 => false,
        6 => false,
        7 => false,
        ),
    'STYLES' => array(
        'KEYWORDS' => array(
            1 => 'color: #0600FF;',        //Functions
            2 => 'color: #008000;',        //Modifiers
            3 => 'color: #0600FF;',        //Custom Functions
            4 => 'color: #804040;',        //Variables
            5 => 'color: #008000;',        //Methods
            6 => 'color: #6A0A0A;',        //Attributes
            7 => 'color: #D36900;'        //Text-based symbols
            ),
        'COMMENTS' => array(
            'MULTI' => 'color: #008080; font-style: italic;'
            ),
        'ESCAPE_CHAR' => array(
            0 => 'color: #000099; font-weight: bold;'
            ),
        'BRACKETS' => array(
            0 => 'color: #D36900;'
            ),
        'STRINGS' => array(
            0 => 'color: #ff0000;'
            ),
        'NUMBERS' => array(
            0 => 'color: #cc66cc;'
            ),
        'METHODS' => array(
            1 => 'color: #006600;'
            ),
        'SYMBOLS' => array(
            0 => 'color: #D36900;'
            ),
        'SCRIPT' => array(
            0 => '',
            1 => '',
            2 => ''
            ),
        'REGEXPS' => array(
            0 => '',
            ),
     ),
    'URLS' => array(),
    'OOLANG' => true,
    'OBJECT_SPLITTERS' => array(
        1 => '.'
        ),
    'REGEXPS' => array(
        0 => array( // object.property
            GESHI_SEARCH => "([[:space:]])([a-zA-Z0-9_]+\..*)([[:space:]])",
            GESHI_REPLACE => '\\2',
            GESHI_MODIFIERS => 'U',
            GESHI_BEFORE => '\\1',
            GESHI_AFTER => '\\3'
            ),
        ),
    'STRICT_MODE_APPLIES' => GESHI_MAYBE,
    'SCRIPT_DELIMITERS' => array(
        0 => array(
            '{#' => '#}'
            ),
        1 => array(
            '{{' => '}}'
            ),
        2 => array(
            '{%' => '%}'
            ),
    ),
    'HIGHLIGHT_STRICT_BLOCK' => array(
        0 => true,
        1 => true,
        2 => true
        )
);

?>
