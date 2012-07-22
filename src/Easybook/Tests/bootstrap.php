<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../../../vendor/autoload.php';

// TODO: this is a temporary fix.
// When executing the test suite with a locale different from en_US, some tests
// fail. My console set es_ES as locale and I got three errors because the
// numeric result of Twig expressions is expressed as 4,8 instead of 4.8
// I've tried setting number format explicitly in Twig with no luck:
// $twig->getExtension('core')->setNumberFormat(0, '.', ',');
setlocale(LC_ALL, 'en_US');
