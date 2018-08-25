<?php declare(strict_types=1);

namespace Easybook\Events;

final class EasybookEvents
{
    /**
     * @var string
     */
    public const PRE_PARSE = 'item.parse.start';

    /**
     * @var string
     */
    public const POST_PARSE = 'item.parse.finish';
}
