<?php declare(strict_types=1);

namespace Easybook\Util;

use Nette\Utils\Strings;

final class Slugger
{
    public function slugify(string $string): string
    {
        return Strings::webalize($string);
    }
}
