<?php declare(strict_types=1);

namespace Easybook\Util;

use Nette\Utils\Strings;

final class Slugger
{
    /**
     * @var string[]
     */
    private $generatedSlugs = [];

    public function slugify(string $string): string
    {
        $slug = Strings::webalize($string);

        $this->generatedSlugs[] = $slug;

        return $slug;
    }

    /**
     * Transforms the original string into a web-safe slug. It also ensures that
     * the generated slug is unique for the entire book (to do so, it stores
     * every slug generated since the beginning of the script execution).
     */
    public function slugifyUniquely($string)
    {
        $slug = Strings::webalize($string);

        // ensure the uniqueness of the slug
        $occurrences = array_count_values($this->generatedSlugs);

        $count = isset($occurrences[$slug]) ? $occurrences[$slug] : 0;
        if ($count > 1) {
            $slug = $slug . '-' . $count;
        }

        return $slug;
    }

    public function resetGeneratedSlugs(): void
    {
        $this->generatedSlugs = [];
    }
}
