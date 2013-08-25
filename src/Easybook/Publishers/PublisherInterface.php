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

interface PublisherInterface {
    /**
     * Checks whether this publisher works in the system where
     * easybook is being executed. This method is useful for
     * the PDF and MOBI publishers, which require some special
     * third-party libraries in order to work.
     *
     * @return bool True if this publisher works in this system
     */
    public function checkIfThisPublisherIsSupported();

    /**
     * It defines the complete workflow followed to publish a book (load its
     * contents, transform them into HTML files, etc.)
     */
    public function publishBook();
}