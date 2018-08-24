<?php declare(strict_types=1);

namespace Easybook\Tests\Util;

use Easybook\Tests\AbstractContainerAwareTestCase;
use Easybook\Util\Slugger;

final class SluggerTest extends AbstractContainerAwareTestCase
{
    /**
     * @var Slugger
     */
    private $slugger;

    protected function setUp(): void
    {
        $this->slugger = $this->container->get(Slugger::class);
    }

    public function testSlugify(): void
    {
        // don't use a dataProvider because it interferes with the slug generation
        $slugs = [
            ['Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet'],
            ['Lorem ipsum !! dolor sit amet', 'lorem-ipsum-dolor-sit-amet'],
            ['Lorem ipsum + dolor * sit amet', 'lorem-ipsum-dolor-sit-amet'],
            ['Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam'],
            ['Ut enim ad / minim || veniam', 'ut-enim-ad-minim-veniam'],
            ['Ut enim _ad minim_ veniam', 'ut-enim-ad-minim-veniam'],
            ['Lorem ipsum dolor sit amet', 'lorem-ipsum-dolor-sit-amet'],
            ['Lorem ipsum dolor ++ sit amet', 'lorem-ipsum-dolor-sit-amet'],
            ['Ut * enim * ad * minim * veniam', 'ut-enim-ad-minim-veniam'],
            ['Ut enim ad minim veniam', 'ut-enim-ad-minim-veniam'],
        ];

        foreach ($slugs as $slug) {
            $string = $slug[0];
            $expectedSlug = $slug[1];

            $this->assertSame($expectedSlug, $this->slugger->slugify($string));
        }
    }
}
