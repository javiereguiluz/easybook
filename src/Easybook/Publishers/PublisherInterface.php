<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Publishers;

interface PublisherInterface
{
    /**
     * It defines the complete workflow followed to publish a book (load its
     * contents, transform them into HTML files, etc.).
     */
    public function publishBook();
}
