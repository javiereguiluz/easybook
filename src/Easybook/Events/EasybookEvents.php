<?php declare(strict_types=1);

namespace Easybook\Events;

/**
 * Defines all the events dispatched by easybook.
 */
final class EasybookEvents
{
    public const PRE_NEW = 'book.new.start';

    public const POST_NEW = 'book.new.finish';

    public const PRE_PUBLISH = 'book.publish.start';

    public const POST_PUBLISH = 'book.publish.finish';

    public const PRE_PARSE = 'item.parse.start';

    public const POST_PARSE = 'item.parse.finish';

    public const PRE_DECORATE = 'item.decorate.start';

    public const POST_DECORATE = 'item.decorate.finish';
}
