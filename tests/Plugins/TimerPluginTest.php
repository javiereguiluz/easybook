<?php declare(strict_types=1);

namespace Easybook\Tests\Plugins;

use Easybook\Events\BaseEvent;
use Easybook\Plugins\TimerPlugin;
use Easybook\Tests\AbstractContainerAwareTestCase;

final class TimerPluginTest extends AbstractContainerAwareTestCase
{
    public function testTimerIsInitialized(): void
    {
        $this->assertSame(null, $app['app.timer.start']);
        $this->assertSame(null, $app['app.timer.finish']);
    }

    public function testTimerPlugin(): void
    {
        $elapsedMicroseconds = 100;

        $event = new BaseEvent($app);
        $plugin = new TimerPlugin();

        $plugin->registerPublicationStart($event);
        usleep($elapsedMicroseconds);
        $plugin->registerPublicationEnd($event);

        $this->assertNotSame(null, $app['app.timer.start']);
        $this->assertNotSame(null, $app['app.timer.finish']);

        $this->assertGreaterThan($app['app.timer.start'], $app['app.timer.finish']);

        $this->assertGreaterThanOrEqual(
            $elapsedMicroseconds / 1000000,
            $app['app.timer.finish'] - $app['app.timer.start']
        );
    }
}
