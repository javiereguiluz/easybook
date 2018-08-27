<?php declare(strict_types=1);

namespace Easybook\Tests\Publisher\PdfPublisher;

use Easybook\Exception\Publisher\RequiredBinFileNotFoundException;
use Easybook\Publisher\PdfPublisher;
use Easybook\Tests\AbstractCustomConfigContainerAwareTestCase;

final class InvalidBinTest extends AbstractCustomConfigContainerAwareTestCase
{
    public function test(): void
    {
        /** @var PdfPublisher $pdfPublisher */
        $pdfPublisher = $this->container->get(PdfPublisher::class);

        $this->expectException(RequiredBinFileNotFoundException::class);
        $pdfPublisher->publishBook('anything');
    }

    protected function provideConfig(): string
    {
        return __DIR__ . '/config/invalid-config.yml';
    }
}
