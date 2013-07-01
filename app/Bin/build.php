<?php
/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 * (c) Matthieu Moquet <matthieu@moquet.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__.'/../../vendor/autoload.php';

use Easybook\Util\Compressor;

// check that this command is executed from the root directory
if ('app/Bin/build.php' != $argv[0]) {
    throw new \RuntimeException(sprintf(
        "\n[ERROR] This command can only be executed from the root directory as follows:\n\n%s\n\n",
        "$ php app/Bin/build.php"
    ));
}

echo "\nBuilding easybook ZIP file\n".str_repeat('=', 80)."\n";

// update vendors
echo "\n > Updating vendors ('composer update' command)\n";
$output = array();
$status = null;
exec("composer update", $output, $status);
if (0 !== $status) {
    throw new \RuntimeException(sprintf(
        "\n[ERROR] There was an error updating vendors:\n%s\n\n",
        implode("\n", $output)
    ));
}

// execute tests
echo "\n > Executing test suite ('phpunit' command)\n";
$output = array();
$status = null;
exec("phpunit", $output, $status);
if (0 !== $status) {
    throw new \RuntimeException(sprintf(
        "\n[ERROR] There was an error executing tests:\n%s\n\n",
        implode("\n", $output)
    ));
}

// build ZIP file
echo "\n > Building the ZIP file\n";
$compressor = new Compressor();
$compressor->build();
