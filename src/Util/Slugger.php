<?php declare(strict_types=1);

namespace Easybook\Util;

use EasySlugger\SluggerInterface;

final class Slugger
{
    /**
     * @var string[]
     */
    private $generatedSlugs = [];
    /**
     * @var SluggerInterface
     */
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function slugify(string $string, string $separator = null, string $prefix = null): string
    {
        $slug = $this->slugger->slugify($string, $separator);
        if ($prefix) {
            $slug = $prefix . $slug;
        }

        $this->generatedSlugs[] = $slug;

        return $slug;
    }

    /**
     * Transforms the original string into a web-safe slug. It also ensures that
     * the generated slug is unique for the entire book (to do so, it stores
     * every slug generated since the beginning of the script execution).
     *
     * @param string $string    The string to slug
     * @param string $separator Used between words and to replace illegal characters
     * @param string $prefix    Prefix to be appended at the beginning of the slug
     *
     * @return string The generated slug
     */
    public function slugifyUniquely($string, $separator = null, $prefix = null)
    {
        $defaultOptions = $this['slugger.options'];

        $separator = $separator ?: $defaultOptions['separator'];
        $prefix = $prefix ?: $defaultOptions['prefix'];

        $slug = $this->slugify($string, $separator, $prefix);

        // ensure the uniqueness of the slug
        $occurrences = array_count_values($this['slugger.generated_slugs']);
        $count = isset($occurrences[$slug]) ? $occurrences[$slug] : 0;
        if ($count > 1) {
            $slug = $slug.$separator.$count;
        }

        return $slug;
    }
}
