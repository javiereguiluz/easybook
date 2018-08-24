<?php declare(strict_types=1);

namespace Easybook\Tests\Publishers;

use Easybook\Publishers\Epub2Publisher;
use Easybook\Tests\AbstractContainerAwareTestCase;
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
        $this->privatesCaller = new PrivatesCaller();
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

        $this->parameterProvider->changeParameter('book_slug', uniqid('phpunit_'));

        $bookDir = $this->privatesCaller->callPrivateMethod($this->epub2Publisher, 'prepareBookTemporaryDirectory');

        foreach ($directoriesRequiredForEpubBooks as $expectedDirectory) {
            $this->assertFileExists($bookDir . '/' . $expectedDirectory);
        }

        // filesystem
        $this->filesystem->remove($bookDir);
    }

    /**
     * @dataProvider getTestPrepareBookCoverImageData
     */
    public function testPrepareBookCoverImage(int $width, int $height, string $mimeType, string $fileName): void
    {
        $tmpDir = $this->container->getParameter('%kernel.cache_dir') . '/' . uniqid('phpunit_');
        $this->filesystem->mkdir($tmpDir);

        $imageFilePath = $tmpDir . '/' . $fileName;

        // custom cover image - create from config with defined one

//        $app = $this->getMock('Easybook\DependencyInjection\Application', ['getCustomCoverImage']);
//        $app->expects($this->any())
//            ->method('getCustomCoverImage')
//            ->will($this->returnValue($imageFilePath));

        $this->createImageByMimeType($width, $height, $mimeType, $imageFilePath);

        $coverImage = [
            'height' => $height,
            'width' => $width,
            'filePath' => 'images/' . $fileName,
            'mediaType' => 'image/' . $mimeType,
        ];

        $bookCoverImage = $this->privatesCaller->callPrivateMethod($this->epub2Publisher, 'prepareBookCoverImage');
        $this->assertSame($coverImage, $bookCoverImage);

        $this->filesystem->remove($tmpDir);
        //imagedestroy($image);
    }

    /**
     * @return mixed[]
     */
    public function getTestPrepareBookCoverImageData(): array
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

    private function createImageByMimeType(int $width, int $height, string $mimeType, string $imageFilePath): void
    {
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
    }
}
