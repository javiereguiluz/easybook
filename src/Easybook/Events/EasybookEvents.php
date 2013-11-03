<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Events;

/**
 * Defines all the events dispatched by easybook
 */
final class EasybookEvents
{
    const PRE_NEW       = 'book.new.start';
    const POST_NEW      = 'book.new.finish';
    const PRE_PUBLISH   = 'book.publish.start';
    const POST_PUBLISH  = 'book.publish.finish';
    const PRE_PARSE     = 'item.parse.start';
    const POST_PARSE    = 'item.parse.finish';
    const PRE_DECORATE  = 'item.decorate.start';
    const POST_DECORATE = 'item.decorate.finish';
}
