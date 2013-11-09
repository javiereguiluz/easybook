# Plugins #

**easybook** is a rather flexible application with lots of configuration
options. However, sometimes your requirements are so specific that cannot be
covered by any configuration option.

In those cases, you can use **plugins**, which allow you to completely modify 
the behavior of **easybook**. During the publication of a book, **easybook**
performs a lot of different tasks: loading the book contents, transforming them
from Markdown to HTML, building the PDF, EPUB or MOBI file, etc.

Whenever **easybook** begins or ends an important tasks, an **event** is 
notified. Plugins can *hook* into any of these events to perform any action
before or after these tasks.

## Creating the first plugin ##

**easybook** uses the [Event Dispatcher component](http://symfony.com/doc/current/components/event_dispatcher)
of Symfony2 to define its plugins. Technically speaking, a plugin is any PHP class stored in the `Resources/Plugins/` directory of your book, whose name 
ends with the word `Plugin` and that implements the `EventSubscriberInterface` 
interface.

Imagine that you want to measure the time elapsed to publish a book. Given that
**easybook** notifies both the begin and the end of the book publication, it's
really easy to create a plugin that *hooks* on both events to register the
timestamp of each moment.

If the plugin is called `Timer`, create a new `TimerPlugin` class in the
`<book>/Resources/Plugins/TimerPlugin.php` file and add the following content:

~~~ .php
// <book>/Resources/Plugins/TimerPlugin.php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TimerPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array();
    }
}
~~~

Now, use the `getSubscribedEvents()` method to define the specific events that
you need to *hook* into. In the next section, you'll find the
[complete list of easybook events](#easybook-events) but for now, just assume
that the event notified when the book publication begins is called
`Events::PRE_PUBLISH` and the other event is called `Events::POST_PUBLISH`:

~~~ .php
// <book>/Resources/Plugins/TimerPlugin.php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;

class TimerPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_PUBLISH  => 'registerPublicationStart',
            Events::POST_PUBLISH => 'registerPublicationEnd',
        );
    }

    public function registerPublicationStart(BaseEvent $event)
    {
        // ...
    }

    public function registerPublicationEnd(BaseEvent $event)
    {
        // ...
    }
}
~~~

First, the `getSubscribedEvents()` method returns an array of events that you 
want to subscribe to and the name of the methods that will be executed when 
those events occur. Therefore, this plugin must define two new methods called
`registerPublicationStart()` and `registerPublicationEnd()`.

The first argument of each method (`BaseEvent $event`) is automatically
injected by **easybook** and it allows to access easily to any application 
property or method. In the next section, you'll discover all the properties of 
the [BaseEvent class](#baseevent-class).

N> The class of the argument injected to the plugin methods depend upon the 
N> kind of event notified. In the above code, the object is an instance of
N> `BaseEvent`, but for other events, the object is an instance of the
N> `ParseEvent` class.

Finally, to register the begin and the end of the book publication, you can 
simply store the timestamp of each moment in some application properties:

~~~ .php
// <book>/Resources/Plugins/TimerPlugin.php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;

class TimerPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_PUBLISH  => 'registerPublicationStart',
            Events::POST_PUBLISH => 'registerPublicationEnd',
        );
    }

    public function registerPublicationStart(BaseEvent $event)
    {
        $event->app->set('app.timer.start', microtime(true));
    }

    public function registerPublicationEnd(BaseEvent $event)
    {
        $event->app->set('app.timer.finish', microtime(true));
    }
}
~~~

## Events defined by easybook ## {#easybook-events}

In order to keep things organized and to avoid the problems introduced by the
[magic strings](http://en.wikipedia.org/wiki/Magic_string), **easybook** 
follows the best practice of defining the event names in a special class:

~~~ .php
namespace Easybook\Events;

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
~~~

The following table explains when is each event notified and the kind of event object injected to the subscriber methods:

| Event           | Injected Object | Notification
| --------------- | --------------- | -----------------------------------
| `PRE_NEW`       | `BaseEvent`     | When creating a new book, after validating its title and before creating the file and directory structure
| `POST_NEW`      | `BaseEvent`     | When creating a new book, after creating its file and directory structure and before displaying the success message to the user
| `PRE_PUBLISH`   | `BaseEvent`     | When publishing a book, after all its configuration has been loaded and validated and after executing the `before_publish` scripts
| `POST_PUBLISH`  | `BaseEvent`     | When publishing a book, after the publication is completed and before executing the `after_publish` scripts
| `PRE_PARSE`     | `ParseEvent`    | When publishing a book, just before each book content is transformed from Markdown into HTML
| `POST_PARSE`    | `ParseEvent`    | When publishing a book, just after each book content is transformed from Markdown into HTML
| `PRE_DECORATE`  | `BaseEvent`     | When publishing a book, just before each book content is decorated with the appropriate Twig template
| `POST_DECORATE` | `BaseEvent`     | When publishing a book, just after each book content is decorated with the appropriate Twig template

### The `BaseEvent` class ### {#baseevent-class}

This is the generic event class that it's used by most of the event 
notifications. It only defines one property:

  * `app`, it stores the object that represents the whole **easybook** application. Access to any application property with `$event->app['property_name']` and modify any property with `$event->app['property_name'] = 'property_value';`

In addition, it defines two methods:

  * `getItem()`, it returns the active book item. When used for example with 
    the `PRE_DECORATE` event, it stores the book item that is about to be
    decorated with the Twig template.
  * `setItem(array $item)`, it allows you to replace the active item by the
    given item. This way you can modify any item configuration or event the
    whole item content.

### The `ParseEvent` class ### {#parseevent-class}

This class extends the previous `BaseEvent` class and adds new methods to ease
the access to the properties of the item being parsed. The only property
defined by this class is the same `app` property explained in the previous
section.

This class defines four methods:

  * `getItem()`, it returns the active item that it's being parsed.
  * `setItem(array $item)`, it allows you to replace the active item being 
    parsed with your own item.
  * `getItemProperty($key)`, it returns the value of the given `$key` property
    of the item. To get for example the original Markdown content of the item
    being parsed, use `$event->getItemProperty('original')`
  * `setItemProperty($key, $value)`, it modifies the value of the `$key` property
    of the item with the `$value` content. To replace for example the original
    Markdown content of the item being parsed, use
    `$event->setItemProperty('original', '...')`

## Creating an advanced plugin ##

### Subscribing to several events ###

In order to write more legible code, you may want to split the plugin 
operations into several methods. When subscribing to the event, instead of
passing a simple string with the method name, pass an array with all the 
method names:

~~~ .php
class MyPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            // three different methods subscribed to the same event
            Events::PRE_PUBLISH  => array(
                'registerPublicationStart',
                'fixPublicationDirectory',
                'prepareBookContents',
            )
        );
    }
~~~

### Event priorities ###

When several methods are subscribed to the same event, the order in which they
are executed is determined according to these rules:

  1. **easybook** loads the plugin classes alphabetically, so the `AaaPlugin` 
    methods are executed before the methods of the `BbbPlugin` class.
  2. Inside a single class, the methods are executed in the same order as they
    were registered.

However, it's really easy to modify this behavior by setting explicitly the
priority of each subscribed method. When subscribing to the event, instead of 
passing a simple string with the method name, pass an array with the method 
name and its priority:

~~~ .php
class MyPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_PUBLISH  => array('firstMethodName', 10),
            Events::POST_PUBLISH => array('secondMethodName', -200),
        );
    }
}
~~~

The priority of each method is defined as an integer (positive or negative). 
The default priority is `0` and higher values mean more priority. You can also
set priority when subscribing several methods to the same event:

~~~ .php
class MyPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            // three different methods subscribed to the same event
            Events::PRE_PUBLISH  => array(
                array('firstMethodName', 10),
                array('secondMethodName', 20),
                array('thirdMethodName', 30),
            )
        );
    }
}
~~~

### Restricting the plugin execution ###

Sometimes the execution of some plugin methods only make sense for a particular
edition or for a specific edition format (e.g. only for PDF books). The easiest
way to restrict the execution of the plugin is to get the edition being 
published and check its name or format:

~~~ .php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;

class MyPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_PUBLISH => 'myMethod',
        );
    }

    public function myMethod(BaseEvent $event)
    {
        if ('pdf' == $event->app->edition('format')) {
            // this code is only executed for PDF books
        }

        if ('my_edition' == $event->app['publishing.edition']) {
            // this code is only executed when publishing the
            // 'my_edition' edition of the book
        }
    }
}
~~~

You can also stop the propagation of the event to prevent other plugins' 
methods from executing. To do so, invoke the `stopPropagation()` method of the 
event object:

~~~ .php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\BaseEvent;

class MyPlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_PUBLISH => 'myMethod',
        );
    }

    public function myMethod(BaseEvent $event)
    {
        // do something ...

        // stop the event propagation (no other method or
        // plugin will be executed to respond to this event)
        $event->stopPropagation();
    }
}
~~~

## Built-in plugins ##

**easybook** relies heavily on plugins to perform some of the most important
tasks when publishing a book. The source code of these plugins is a good
resource to learn how to develop advanced plugins:

  * `CodePlugin`, it is used for syntax highlighting the code blocks.
  * `ImagePlugin`, it decorates each image with its own Twig template and label. It also fixes the URL of the images when the books is published as a website and it generates the `lof` (*list of figures*) special content. 
  * `LinkPlugin`, it fixes the internal links of the books published as websites.
  * `ParserPlugin`, it adds labels to the section headings and it performs some minor fixes in the HTML generated by the Markdown transformer.
  * `TablePlugin`, it decorates each table with its own Twig template and label. It also generates the `lot` (*list of tables*) special content. 
  * `TimerPlugin`, it's used to measure the time elapsed to publish a book.
