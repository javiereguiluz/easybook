<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Validators
{
    public static function validateBookTitle($title)
    {
        if (null == $title || '' == $title) {
            throw new \InvalidArgumentException("ERROR: The title cannot be empty.");
        }

        return $title;
    }
    
    public static function validateBookDir($dir)
    {
        if (!file_exists($dir)) {
            throw new \InvalidArgumentException(sprintf(
                "ERROR: The '%s' directory doesn't exist.", $dir
            ));
        }

        return $dir;
    }
}