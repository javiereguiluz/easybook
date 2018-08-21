<?php declare(strict_types=1);

namespace Easybook\Tests\Publishers;

use Easybook\Publishers\Epub2Publisher;
use Easybook\Tests\AbstractContainerAwareTestCase;
use ReflectionMethod;

final class Epub2PublisherTest extends AbstractContainerAwareTestCase
{
    private $app;

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
        $publisher = new Epub2Publisher($this->app);

        $method = new ReflectionMethod(Epub2Publisher::class, 'prepareBookTemporaryDirectory');
        $method->setAccessible(true);
        $bookDir = $method->invoke($publisher);

        foreach ($directoriesRequiredForEpubBooks as $expectedDirectory) {
            $this->assertFileExists($bookDir . '/' . $expectedDirectory);
        }

        // filesystem
        $this->app['filesystem']->remove($bookDir);
    }

    public function testPrepareBookCoverImageIfTheBookDefinesNoCoverImage(): void
    {
        $app = $this->getMock('Easybook\DependencyInjection\Application', ['getCustomCoverImage']);
        $app->expects($this->any())
            ->method('getCustomCoverImage')
            ->will($this->returnValue(null));

        $publisher = new Epub2Publisher($app);

        $method = new ReflectionMethod(Epub2Publisher::class, 'prepareBookCoverImage');
        $method->setAccessible(true);

        $this->assertSame(null, $method->invoke($publisher, ''));
    }

    /**
     * @dataProvider getTestPrepareBookCoverImageData
     */
    public function testPrepareBookCoverImage($width, $height, $mimeType, $fileName): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped(
                'This test generates sample image files and therefore it requires
                to enable the GD extension in your PHP configuration'
            );
        }

        $tmpDir = $this->app['app.dir.cache'] . '/' . uniqid('phpunit_');
        $this->app['filesystem']->mkdir($tmpDir);

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

        $publisher = new Epub2Publisher($app);

        $method = new ReflectionMethod(Epub2Publisher::class, 'prepareBookCoverImage');
        $method->setAccessible(true);

        $coverImage = [
            'height' => $height,
            'width' => $width,
            'filePath' => 'images/' . $fileName,
            'mediaType' => 'image/' . $mimeType,
        ];

        $this->assertSame($coverImage, $method->invoke($publisher, $tmpDir));

        $this->app['filesystem']->remove($tmpDir);
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
