<?php declare(strict_types=1);

namespace Easybook\Publisher;

/**
 * @todo
 */
final class PdfPublisher extends AbstractPublisher
{
    public function getFormat(): string
    {
        return 'pdf';
    }

    protected function assembleBook(): void
    {
        return;
    }
}
