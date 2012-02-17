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
    public static function validateNonEmptyString($id, $value)
    {
        if (null == $value || '' == trim($value)) {
            throw new \InvalidArgumentException("ERROR: The $id cannot be empty.");
        }

        return $value;
    }
    
    public static function validateBookSlug($slug)
    {
        if (!preg_match('/^[a-zA-Z0-9\-]+$/', $slug)) {
            throw new \InvalidArgumentException(
                "ERROR: The slug can only contain letters, numbers and dashes (no spaces)"
            );
        }
        
        return $slug;
    }
    
    public static function validateDir($dir)
    {
        if (!file_exists($dir)) {
            throw new \InvalidArgumentException(sprintf(
                "ERROR: The '%s' directory doesn't exist.", $dir
            ));
        }

        return $dir;
    }
}