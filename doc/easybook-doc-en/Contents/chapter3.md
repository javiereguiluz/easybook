# Publishing your third book #

## Creating your own theme ##

**easybook** themes make your books look beautiful by default. However, your
books should define their own styles to fine-tune their appearance. To do this,
create a directory called `Resources` within the directory of the book and
inside it, create another directory called `Templates`.

You can now include your own templates in the `Resources/Templates/` to apply
them to the published book. The name of the template must match the element type
(`chapter`, `dedication`, `author`, etc..) and its extension should be `.twig`
because they are always created with the Twig templating language. Consequently,
if you want to modify the design of the chapters, you must create a template
called `chapter.twig`.

When you publish a book, **easybook** first looks for the templates in the
following directories and in the following order (if none of these templates are
found, it will use the default template):

  1. `<book>/Resources/Templates/<edition-name>/template.twig`, allows you to
  modify the design for each edition. The directory inside `Templates/` must
  match exacly the name of the edition being published.
  2. `<book>/Resources/Templates/<edition-type>/template.twig`, allows you to
  modify the design for all the editions of the same type. The directory inside
  `Templates/` must be named `epub`, `html`, `html_chunked` or `pdf`.
  3. `<book>/Resources/Templates/template.twig`, applies the same same design to
  all editions. The template is located in the `Resources/Templates` directory
  without including it in any subdirectory. This option is rarely used because
  it usually doesn't make sense to apply the same style regardless of whether
  content is converted into a PDF file or a website.

As explained in the previous chapter, the templates have access to a variable
named `item` with all the information about the item that is being decorated
and three global variables with information about the book (`book`), the edition
being published (`edition`) and the entire application (`app`).

### Available templates per edition ###

The following list shows the default templates included in `pdf` and `html` type
editions (their contents obviously varies from one type to the other):

  * A template for each of the 21 content types: `acknowledgement.twig`,
  `afterword.twig`, `appendix.twig`, `author.twig`, `chapter.twig`,
  `conclusion.twig`, `cover.twig`, `dedication.twig`, `edition.twig`,
  `epilogue.twig`, `foreword.twig`, `glossary.twig`, `introduction.twig`,
  `license.twig`, `lof` (list of figures), `lot` (list of tables), `part.twig`,
  `preface.twig`, `prologue.twig`, `title.twig` and `toc.twig` (table of contents).
  * `book.twig`, this is the final template that assembles all the book contents.
  * `code.twig`, the template used to decorate all the code listings included in
  the book.
  * `figure.twig`, the template used to decorate all the images included in the
  book.
  * `table.twig`, the template used to decorate all the tables included in the
  book.

The `html_chunked` edition type doesn't include a different template for each
content type. It onle defines the following five templates:

  * `code.twig`, the template used to decorate all the code listings included in
  the book.
  * `figure.twig`, the template used to decorate all the images included in the
  book.
  * `index.twig`, the template that generates the front page of the website. By
  default it includes the following contents: `cover`, `toc`, `edition` and
  `license`.
  * `chunk.twig`, generic template applied to all the rest of the content types.
  This template must only include the contents of the item being decorated. The
  common features of the website are defined in the `layout.twig` template.
  * `layout.twig`, generic template applied to all the content types, including
  the front page, after decorating them with their own templates. This is the
  best template to include the common features of the website, such as header,
  footer and links to CSS and JavaScript files.
  * `table.twig`, the template used to decorate all the tables included in the
  book.

The `epub` edition type defines the following ten templates:

  * `code.twig`, `figure.twig`, `chunk.twig`, `layout.twig` and `table.twig` are
  the same templates and have the same meaning as for the `html_chunked` edition.
  * `cover.twig`, the template used to generate the book cover, which can also
  use your own image, as explained in the next sections.
  * `content.opf.twig`, the template that generates `content.opf` file listing
  all the book contents.
  * `toc.ncx.twig`, the template that generates the `toc.ncx` file for the book
  table of contents.
  * `container.xml.twig` y `mimetype.twig`, templates that generate other minor
  but necessary files for `.epub` format books.

### Stylesheets ###

In addition to Twig templates, themes include a custom CSS stylesheet for each
edition type. These default styles are applied whenever the `include_styles`
option of the edition is `true`. If you don't want to apply these styles, delete
the option or give it a `false` value.

However, the most common scenario is to maintain the default **easybook** styles
and then apply your custom CSS to include new styles or modify them. To do this,
add a file called `style.css` within the directory:

  1. `<book>/Resources/Templates/<edition-name>/style.css`, if you want to apply
  the styles just to one specific edition.
  2. `<book>/Resources/Templates/<edtion-type>/style.css`, if you want to apply
  the same styles to all the editions of the same type (`epub`, `html`,
  `html_chunked` or `pdf`).
  3. `<book>/Resources/Templates/style.css`, if you want to apply the same styles
  to all the editions of the book.

### Book cover ###

**easybook** automatically generates a book cover for every edition type. Since
this is a text-based cover, it's too dull for most of the books.

If you want to use your very own book cover image in `epub` editions, just
create a `cover.jpg` file and copy it into one of these directories:

  1. `<book>/Resources/Templates/<edition-name>/cover.jpg`, to use the image
  only for this specific edition.
  2. `<book>/Resources/Templates/epub/cover.jpg`, to use the same cover image
  for every `epub` edition.

The cover image format must be JPEG, because this is the most supported format
in e-book readers. In order to visualize it correctly in advanced readers such
as the iPad, create a large image (at least 800 pixel height) and save it in
color.

## Plugins ##

Despite being a simple application, **easybook** easily adapts to your requirements.
The previous chapters showed lots of examples of this flexibility, but the
**plugins** explained in this section are by far the most powerful example.

A plugin allows you to completely modify the behavior of **easybook**. Imagine
you want to modify the original Markdown content of the chapters  before
converting it to HTML. This is really easy because, before converting a content,
**easybook** notifies that it's going to convert it. Then, your book can create
a plugin that *listens* to these events and executes some code when an specific
event is notified.

Technically, plugins are based on the subscribers defined by the event dispatcher component of Symfony2. They are regular PHP classes whose name always ends with
the word `Plugin`. The following code shows the source of `TimerPlugin.php`, the
plugin used by **easybook** to measure how long it takes to publish a book:

    [php]
    <?php
    namespace Easybook\Plugins;
    
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;
    use Easybook\Events\EasybookEvents as Events;
    use Easybook\Events\BaseEvent;

    class TimerPlugin implements EventSubscriberInterface
    {
        static public function getSubscribedEvents()
        {
            return array(
                Events::PRE_PUBLISH  => 'onStart',
                Events::POST_PUBLISH => 'onFinish',
            );
        }

        public function onStart(BaseEvent $event)
        {
            $event->app->set('app.timer.start', microtime(true));
        }

        public function onFinish(BaseEvent $event)
        {
            $event->app->set('app.timer.finish', microtime(true));
        }
    }

**easybook** plugins must implement `EventSubscriberInterface` interface, which
in turn forces to define a method called `getSubscribedEvents()`. This method
simply returns an array of events that you want to subscribe to and the name of
the methods that are executed when those events occur.

**easybook** currently has six events, defined in `Easybook\Events\EasybookEvents`
class:

  * `Events::PRE_PUBLISH`, notified before starting the publication of the book
  but after setting the value of all the `publishing.*` internal variables.
  * `Events::POST_PUBLISH`, notified after finishing the publication of the book
  but before showing the success/failure messages to the user.
  * `Events::PRE_PARSE`, notified before starting the conversion of the original
  element content (usually written in Markdown format).
  * `Events::POST_PARSE`, notified after the original content has been fully
  converted (usually this converted content is HTML).
  * `Events::PRE_NEW`, notified before starting the creation of the new book,
  just before starting the file an directory structure creation.
  * `Events::POST_NEW`, notified just before the structure of the new book has
  been created, but before showing the success/failure messages to the user.

The methods that are executed in response to events receive as first parameter
an object whose type depends on the notified event. In general, events receive
a `BaseEvent` object, but the events related to content parsing receive an
object of type `ParseEvent` (which in turn inherits from `BaseEvent`).

You can access any property and service of the application through the event
object using `$event-> app`:

    [php]
    // ...

    public function onStart(BaseEvent $event)
    {
        $event->app->set('app.timer.start', microtime(true));
    }

If you want to add plugins to your own book, create a directory called `Plugins`
inside the `Resources` directory of your book (create the latter one if it
doesn't exist). Then add as many PHP classes as you want, but make their name
always end in `Plugin.php`. Do not add any namespace to your plugin classes.

The following example shows a plugin that modifies the contents of the book
before and after its conversion. First, the plugin modifies the original Markdown
content to put in bold all occurrences of the word *easybook*. Then, it
modifies the HTML content to add a `class` attribute to all these occurrences:

    [php]
    <?php
    use Symfony\Component\EventDispatcher\EventSubscriberInterface;
    use Easybook\Events\EasybookEvents as Events;
    use Easybook\Events\ParseEvent;

    class BrandingPlugin implements EventSubscriberInterface
    {
        static public function getSubscribedEvents()
        {
            return array(
                Events::PRE_PARSE  => 'onItemPreParse',
                Events::POST_PARSE => 'onItemPostParse',
            );
        }
    
        public function onItemPreParse(ParseEvent $event)
        {
            $txt = str_replace(
                'easybook',
                '**easybook**',
                $event->getOriginal()
            );
            
            $event->setOriginal($txt);
        }
        
        public function onItemPostParse(ParseEvent $event)
        {
            $html = str_replace(
                '<strong>easybook</strong>',
                '<strong class="branding">easybook</strong>',
                $event->getContent()
            );
            
            $event->setContent($html);
        }
    }

The event object received by plugins related to content parsing is of type
`ParseEvent`. In addition to application access (`$event->app`), this object has
getters and setters for all the properties of the parsed object.

The event `Events::PRE_PARSE` is notified before the conversion, so you only have
access to the original content (`getOriginal()`). In contrast, the event
`Events::POST_PARSE` is notified after the conversion, so it doesn't make sense
to modify the original content but the converted content (`getContent()`).

## Advanced features ##

### Different directories per book ###

Unless stated otherwise, the books are created in the `doc/` directory of
**easybook**. If you want to save the contents in any other directory, add the
`--dir` option when creating and/or publishig the book:

    [cli]
    $ ./book new "The Origin of Species" --dir="/Users/javier/books"
    (the book is created in "/Users/javier/books/the-origin-of-species")
    
    $ ./book publish the-origin-of-species print --dir="/Users/javier/books"
    (the book is published in "/Users/javier/books/the-origin-of-species/Output/print/book.pdf")

### Syntax highlighting ###

If you use **easybook** to write technical books or programming related
documentation, you can automatically highlight the code listings. First, set
the option `highlight_code` to `true` in any edition with syntax highlighting.

Then, set the programming language in the listings you want to color. Imagine
that you have the following PHP code:

    public function onStart(BaseEvent $event)
    {
        $event->app->set('app.timer.start', microtime (true));
    }

Add `[php]` as the first line of the listing to highlight its code:

    [code]
    [php]
    public function onStart(BaseEvent $event)
    {
        $event->app->set('app.timer.start', microtime (true));
    }

The result is the following code with syntax highlighting:

    [php]
    public function onStart(BaseEvent $event)
    {
        $event->app->set('app.timer.start', microtime (true));
    }

**easybook** uses the [GeSHi library](http://qbnz.com/highlighter) to highlight
the code listings. Therefore, it supports more than 200 programming languages
​​(`[php]`, `[java]`, `[c]`, `[javascript]`, `[ruby]`, `[python]`, `[perl]`,
`[erlang]`, `[haskell]`, ...), markup languages ​​(`[html]`, `[yaml]`, `[xml]`,
...) and configuration (`[ini]`, `[apache]`, ...).

### Advanced configuration ###

**easybook** doesn't restrict the configuration options that you can set on your
book, editions and contents. **easybook** always puts you in control, so you can
define all the new settings that you may need.

Do you want to show the price of the book on the cover? Add a new option called
`price` under the `book` option of `config.yml` file:

    [yaml]
    book:
        # ...
        price: 10

Now you can use this option in any template with the following expression:
`{{ book.price }}`.  Do you want to use different CSS frameworks to generate
the book website? Add a new option called `framework` in some editions:

    [yaml]
    editions:
        my_website1:
            format:    html_chunked
            framework: twitter_bootstrap
            # ...
        
        my_website2:
            extends:   my_website1
            framework: 960_gs

This new option is now available in any template through the following expression:
`{{ edition.framework }}`. Finally, you can also add new options to the contents
of the book.  Do you want to indicate the estimated reading time in each chapter?
Add the following `time` option for `chapter` elements:

    [yaml]
    contents:
        - { element: cover }
        - ...
        - { element: chapter, number: 1, ..., time: 20 }
        - { element: chapter, number: 2, ..., time: 12 }
        - ...

And now you can add `{{ item.config.time }}` in `chapter.twig` template to show
the estimated reading time for each chapter.

### Defining new content types ###

It's uncommon and generally unneeded, but you can also define new content types
besides the 21 types included in **easybook**. Imagine that you want to include
a page with a humorous cartoon between some chapters. Let's call this new content
type `cartoon`.

If pages of type `cartoon` include few contents (a picture and its caption for
example), it's better to define these contents directly in the  `config.yml` file:

    [yaml]
    contents:
        - { element: cover }
        - ...
        - { element: chapter, number: 1, ... }
        - { element: cartoon, image: cartoon1.png, caption: '...' }
        - { element: chapter, number: 2, ... }
        - ...

Then, create a custom `cartoon.twig` template in your book `Resources/Templates/`
directory:

    [twig]
    <div class="page:cartoon">
        <img src="{{ item.config.image }}" />
        <p>{{ item.config.caption }}</p>
    </div>

In contrast, if pages of type `cartoon` include a lot of contents, it's better
to create a content file for each of these elements:

    [yaml]
    contents:
        - { element: cover }
        - ...
        - { element: chapter, number: 1, ... }
        - { element: cartoon, content: cartoon1.md }
        - { element: chapter, number: 2, ... }
        - ...

Then, display this content with the following simplified `cartoon.twig` template:

    [twig]
     <div class="page:cartoon">
         {{ item.content }}
     </div>

Finally, you can also combine these two methods creating a file with the contents
and adding several configuration options in `config.yml`. The template can easily
use both sources of information:

    [twig]
     <div class="page:cartoon">
         <img src="{{ item.config.image }}" />
        
         {{ item.content }}
     </div>

That's it! You can now use the new `cartoon` content type in your book and you
can create new content types following the same steps explained above.

### easybook internals ###

**easybook** flexibility allows you to create advanced books without effort and
without having to study the source code of the application. However, to master
**easybook** you'll have to dive into the guts of the application.

The core and philosophy of **easybook** share many similarities with the source
code of [Silex](http://silex.sensiolabs.org/), a PHP microframework that has
also been created with the Symfony components. If you don't know Silex, we
recommend that you study it and use it to create a demo application. Then, it'll
very easy for you to understand the source code of **easybook**.

The most important class of **easybook** is
`src/Easybook/DependencyInjection/Application.php`. This class follows the
*dependency injection* pattern, it's created with
[Pimple component](http://pimple.sensiolabs.org/) and contains all the variables,
functions and services of the application.

The most interesting command of **easybook** is `publish`, which publishes an
specific edition of the book. Internally it uses a class of type *publisher*,
which depends on the type of edition that is published (`epub`, `html`,
`html_chunked` or `pdf`). The details of each *publisher* vary, but its basic
operation is always the same:

    [php]
    public function publishBook()
    {
        $this->loadContents();
        $this->parseContents();
        $this->decorateContents();
        $this->assembleBook();
    }

First the contents of the book, defined in the `contents` option of `config.yml`
file, are loaded (`loadContents()`) . Then, each content is parsed (`parseContents()`)
to convert it from its original format to the format needed by this *publisher*.

At the moment **easybook** only supports Markdown as original format. If you want
to add support for other formats, you have to create a new parser (dig into the
Markdown parser to know how to create it) and you also have to change the
`parseContents()` method of the *publisher*.

After converting all contents to the desired format (usually HTML) they are
decorated using Twig templates (`decorateContents()`). Finally, the
`assembleBook()` method is responsible for creating the published book. This is
the most unique method of the *publishers*, as sometimes it has to create a PDF
file, sometimes just an HTML file and other times it must create an entire
website with many HTML pages.
