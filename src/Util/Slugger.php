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
}
