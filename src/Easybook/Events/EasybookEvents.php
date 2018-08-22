<?php declare(strict_types=1);

namespace Easybook\Events;

/**
 * Defines all the events dispatched by easybook.
 */
final class EasybookEvents
{
    /**
     * @var string
     */
    public const PRE_NEW = 'book.new.start';

    /**
     * @var string
     */
    public const POST_NEW = 'book.new.finish';

    /**
     * @var string
     */
    public const PRE_PUBLISH = 'book.publish.start';

    /**
     * @var string
     */
    public const POST_PUBLISH = 'book.publish.finish';

    /**
     * @var string
     */
    public const PRE_PARSE = 'item.parse.start';

    /**
     * @var string
     */
    public const POST_PARSE = 'item.parse.finish';

    /**
     * @var string
     */
    public const PRE_DECORATE = 'item.decorate.start';

    /**
     * @var string
     */
    public const POST_DECORATE = 'item.decorate.finish';
}
