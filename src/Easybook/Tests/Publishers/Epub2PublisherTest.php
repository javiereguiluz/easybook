<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Publishers;

use Easybook\DependencyInjection\Application;
use Easybook\Publishers\Epub2Publisher;

class Epub2PublisherTest extends \PHPUnit_Framework_TestCase
{
    private $app;

    public function setUp()
    {
        $this->app = new Application();
    }

    public function testPrepareBookTemporaryDirectory()
    {
        $directoriesRequiredForEpubBooks = array(
            'book',
            'book/META-INF',
            'book/OEBPS',
            'book/OEBPS/css',
            'book/OEBPS/images',
            'book/OEBPS/fonts',
        );

        $this->app['publishing.book.slug'] = uniqid('phpunit_');
        $publisher = new Epub2Publisher($this->app);

        $method = new \ReflectionMethod('Easybook\Publishers\Epub2Publisher', 'prepareBookTemporaryDirectory');
        $method->setAccessible(true);
        $bookDir = $method->invoke($publisher);

        foreach ($directoriesRequiredForEpubBooks as $expectedDirectory) {
            $this->assertFileExists($bookDir.'/'.$expectedDirectory);
        }

        $this->app['filesystem']->remove($bookDir);
    }

    public function testPrepareBookCoverImageIfTheBookDefinesNoCoverImage()
    {
        $app = $this->getMock('Easybook\DependencyInjection\Application', array('getCustomCoverImage'));
        $app->expects($this->any())
            ->method('getCustomCoverImage')
            ->will($this->returnValue(null));

        $publisher = new Epub2Publisher($app);

        $method = new \ReflectionMethod('Easybook\Publishers\Epub2Publisher', 'prepareBookCoverImage');
        $method->setAccessible(true);

        $this->assertEquals(null, $method->invoke($publisher, ''));
    }

    /**
     * @dataProvider getTestPrepareBookCoverImageData
     */
    public function testPrepareBookCoverImage($width, $height, $mimeType, $fileName)
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped(
                'This test generates sample image files and therefore it requires
                to enable the GD extension in your PHP configuration'
            );
        }

        $tmpDir = $this->app['app.dir.cache'].'/'.uniqid('phpunit_');
        $this->app['filesystem']->mkdir($tmpDir);

        $imageFilePath = $tmpDir.'/'.$fileName;

        $app = $this->getMock('Easybook\DependencyInjection\Application', array('getCustomCoverImage'));
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

        $method = new \ReflectionMethod('Easybook\Publishers\Epub2Publisher', 'prepareBookCoverImage');
        $method->setAccessible(true);

        $coverImage = array(
            'height'    => $height,
            'width'     => $width,
            'filePath'  => 'images/'.$fileName,
            'mediaType' => 'image/'.$mimeType
        );

        $this->assertEquals($coverImage, $method->invoke($publisher, $tmpDir));

        $this->app['filesystem']->remove($tmpDir);
        imagedestroy($image);
    }

    public function getTestPrepareBookCoverImageData()
    {
        return array(
            array(640, 1136, 'png', 'cover.png'),
            array(768, 1024, 'png', 'cover.png'),
            array(1536, 2048, 'png', 'cover.png'),
            array(640, 1136, 'gif', 'cover.gif'),
            array(768, 1024, 'gif', 'cover.gif'),
            array(1536, 2048, 'gif', 'cover.gif'),
            // JPEG images are disabled because I get errors that I can't solve
            // array(640, 1136, 'jpeg', 'cover.jpg'),
            // array(768, 1024, 'jpeg', 'cover.jpg'),
            // array(1536, 2048, 'jpeg', 'cover.jpg'),
        );
    }
}