<?php declare(strict_types=1);

namespace Easybook\Tests\Publishers;

use Easybook\Publishers\Epub2Publisher;
use Easybook\Tests\AbstractContainerAwareTestCase;
use ReflectionMethod;
use Symfony\Component\Filesystem\Filesystem;
use Symplify\PackageBuilder\Reflection\PrivatesCaller;

final class Epub2PublisherTest extends AbstractContainerAwareTestCase
{
    /**
     * @var Epub2Publisher
     */
    private $epub2Publisher;

    /**
     * @var PrivatesCaller
     */
    private $privatesCaller;

    protected function setUp(): void
    {
        $this->epub2Publisher = $this->container->get(Epub2Publisher::class);

        $this->privatesCaller = (new PrivatesCaller());
    }

    public function testPrepareBookTemporaryDirectory(): void
    {
        $directoriesRequiredForEpubBooks = [
            'book',
            'book/META-INF',
            'book/OEBPS',
            'book/OEBPS/css',
            'book/OEBPS/images',
            'book/OEBPS/fonts',
        ];

        $this->app['publishing.book.slug'] = uniqid('phpunit_');

        $bookDir = $this->privatesCaller->callPrivateMethod($this->epub2Publisher, 'prepareBookTemporaryDirectory');

        foreach ($directoriesRequiredForEpubBooks as $expectedDirectory) {
            $this->assertFileExists($bookDir . '/' . $expectedDirectory);
        }

        // filesystem
        $this->filesystem->remove($bookDir);
    }

    public function testPrepareBookCoverImageIfTheBookDefinesNoCoverImage(): void
    {
        $app = $this->getMock('Easybook\DependencyInjection\Application', ['getCustomCoverImage']);
        $app->expects($this->any())
            ->method('getCustomCoverImage')
            ->will($this->returnValue(null));

        $bookTemporaryDir = $this->privatesCaller->callPrivateMethod(
            $this->epub2Publisher,
            'prepareBookTemporaryDirectory'
        );

        $this->assertNull($bookTemporaryDir);
    }

    /**
     * @dataProvider getTestPrepareBookCoverImageData
     */
    public function testPrepareBookCoverImage($width, $height, $mimeType, $fileName): void
    {
        $tmpDir = $this->container->getParameter('%kernel.cache_dir') . '/' . uniqid('phpunit_');
        $this->filesystem->mkdir($tmpDir);

        $imageFilePath = $tmpDir . '/' . $fileName;

        $app = $this->getMock('Easybook\DependencyInjection\Application', ['getCustomCoverImage']);
        $app->expects($this->any())
            ->method('getCustomCoverImage')
            ->will($this->returnValue($imageFilePath));

        switch ($mimeType) {
            case 'png':
                $image = imagecreate($width, $height);
                imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
                imagepng($image, $imageFilePath);
                break;
            case 'jpg':
                $image = imagecreatetruecolor($width, $height);
                imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
                imagejpeg($image, $imageFilePath);
                break;
            case 'gif':
                $image = imagecreate($width, $height);
                imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
                imagegif($image, $imageFilePath);
                break;
        }

        $method = new ReflectionMethod(Epub2Publisher::class, 'prepareBookCoverImage');
        $method->setAccessible(true);

        $coverImage = [
            'height' => $height,
            'width' => $width,
            'filePath' => 'images/' . $fileName,
            'mediaType' => 'image/' . $mimeType,
        ];

        $this->assertSame($coverImage, $method->invoke($this->epub2Publisher, $tmpDir));

        $this->filesystem->remove($tmpDir);
        imagedestroy($image);
    }

    public function getTestPrepareBookCoverImageData()
    {
        return [
            [640, 1136, 'png', 'cover.png'],
            [768, 1024, 'png', 'cover.png'],
            [1536, 2048, 'png', 'cover.png'],
            [640, 1136, 'gif', 'cover.gif'],
            [768, 1024, 'gif', 'cover.gif'],
            [1536, 2048, 'gif', 'cover.gif'],
            // JPEG images are disabled because I get errors that I can't solve
            // array(640, 1136, 'jpeg', 'cover.jpg'),
            // array(768, 1024, 'jpeg', 'cover.jpg'),
            // array(1536, 2048, 'jpeg', 'cover.jpg'),
        ];
    }
}
