<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Tests\Plugins;

use Easybook\DependencyInjection\Application;
use Easybook\Events\BaseEvent;
use Easybook\Plugins\TimerPlugin;
use Easybook\Tests\TestCase;

class TimerPluginTest extends TestCase
{
    public function testTimerIsInitialized()
    {
        $app = new Application();

        $this->assertEquals(null, $app['app.timer.start']);
        $this->assertEquals(null, $app['app.timer.finish']);
    }

    public function testTimerPlugin()
    {
        $elapsedMicroseconds = 100;

        $app    = new Application();
        $event  = new BaseEvent($app);
        $plugin = new TimerPlugin();

        $plugin->registerPublicationStart($event);
        usleep($elapsedMicroseconds);
        $plugin->registerPublicationEnd($event);

        $this->assertNotEquals(null, $app['app.timer.start']);
        $this->assertNotEquals(null, $app['app.timer.finish']);

        $this->assertGreaterThan(
            $app['app.timer.start'],
            $app['app.timer.finish']
        );

        $this->assertGreaterThanOrEqual(
            $elapsedMicroseconds / 1000000,
            $app['app.timer.finish'] - $app['app.timer.start']
        );
    }
}