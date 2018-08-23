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
    /**
     * @var string
     */
    private $sluggerSeparator;
    /**
     * @var string
     */
    private $sluggerPrefix;

    public function __construct(SluggerInterface $slugger, string $sluggerSeparator, string $sluggerPrefix)
    {
        $this->slugger = $slugger;
        $this->sluggerSeparator = $sluggerSeparator;
        $this->sluggerPrefix = $sluggerPrefix;
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
        $separator = $separator ?: $this->sluggerSeparator;
        $prefix = $prefix ?: $this->sluggerPrefix;

        $slug = $this->slugify($string, $separator, $prefix);

        // ensure the uniqueness of the slug
        $occurrences = array_count_values($this->generatedSlugs);

        $count = isset($occurrences[$slug]) ? $occurrences[$slug] : 0;
        if ($count > 1) {
            $slug = $slug . $separator . $count;
        }

        return $slug;
    }

    public function resetGeneratedSlugs(): void
    {
        $this->generatedSlugs = [];
    }
}
