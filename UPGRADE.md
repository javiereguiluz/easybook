# UPGRADE guide #

## Upgrade to easybook 5.0 ##

### The directory for the code highlighting cache has changed ###

This internal change should not affect to any **easybook** user.
The directory where the highlighted code is cached now is called
`CodeHighlighter/` instead of `GeSHi/`. This is needed to prepare
**easybook** to support more code highlighting libraries in addition
to the current GeSHi library.

### `ParserEvent` no longer supports magic methods ###

The `ParseEvent $event` object dispatched with some events no longer supports
the magic setter and getter methods. The reason is that this *feature* is
considered a bad programming practice. Use the `get|setItemProperty()` methods
instead:

```php
// BEFORE
class MyPlugin implements EventSubscriberInterface
{
    static public function getSubscribedEvents()
    {
        return array(
            Events::PRE_PARSE  => 'onItemPreParse',
        );
    }
    
    public function onItemPreParse(ParseEvent $event)
    {
        $txt = $event->getOriginal();

        // ...
        
        $event->setOriginal($txt);
    }
}


// AFTER
class MyPlugin implements EventSubscriberInterface
{
    static public function getSubscribedEvents()
    {
        return array(
            Events::PRE_PARSE  => 'onItemPreParse',
        );
    }
    
    public function onItemPreParse(ParseEvent $event)
    {
        $txt = $event->getItemProperty('original');

        // ...
        
        $event->setItemProperty('original', $txt);
    }
}
```

### `Epub2` format is replaced by `Epub` format ###

The previous versions of **easybook** always used the `epub` format but they
allowed to use `epub2` as an alias. This `epub2` format is no longer supported
and you must always use `epub`.

**If you have created your own theme, you must rename its `Epub2/` directory
to `Epub/` and everything will continue working as expected.**

### Configuring easybook parameters ###

You can now override any **easybook** parameter easily in your books. Create a
new `easybook` section in your `config.yml` book and define the new value of
each parameter under the `parameters` key:

```yaml
easybook:
    parameters:
        kindlegen.command_options: '-c0 -gif verbose'
        kindlegen.path:            '/path/to/utils/KindleGen/kindlegen'
        publishing.dir.output:     '/my/path/for/books/my-book'
        slugger.options:
            separator:             '_'

book:
    title:            easybook documentation
    author:           Javier Eguiluz
    edition:          First edition
    language:         en
    publication_date: ~
    # ...
```

You can override any `src/Easybook/DependencyInjection/Application.php` parameter,
as explained in the updated chapter 3 of the documentation.

### Added support for Kindle readers and apps ###

**easybook** can now generate Kindle-compatible ebooks, thanks to
the new `mobi` format. In most cases, you should create a new
edition extending your regular ePub edition (because
their configuration options should be nearly identical).

```yaml
book:
    # ...

    contents:
        # ...

    editions:
        kindle:
            extends:         ebook
            format:          mobi

        ebook:
            format:          epub
            include_styles:  true
            highlight_cache: true
            highlight_code:  false
            labels:          ['appendix', 'chapter', 'figure']
            theme:           clean
            toc:
                deep:        1
                elements:    ["appendix", "chapter"]
```

### Removed the `use_html_toc` option ###

This was an option needed for .ePub books in order to transform
them to .mobi books. Now that easybook can generate Kindle
compatible .mobi ebooks, this option is no longer needed.

### Custom CSS styles ###

Themes and templates have been greatly improved. If you have designed a custom
CSS for books created with previous versions of **easybook** you must change
some selectors. This is a one-time and easy-to-do change:

```css
/* your current CSS */
.item\:cover    { ... }
.item\:chapter  { ... }
.item\:appendix { ... }
...

/* change the previous selectors with the following */
.item.cover    { ... }
.item.chapter  { ... }
.item.appendix { ... }
...
```

### Customize books ###

**easybook** now includes a command to easily generate the needed CSS to customize
the book design. Execute the command passing to it the slug of the book and the
name of the edition to customize:

```
$ ./book customize the-origin-of-species ebook
```

If the command executes successfully, the following CSS will be created:

  <easybook-dir>/doc/the-origin-of-species/Resources/Templates/ebook/style.css

### New `images_base_dir` option ###

**easybook** now allows to set the base directory for web images. The default
value (`images/`) maintains backwards compatibility.

This setting is useful for web books embedded in websites. If you want for
example to serve book images from the `/images/books/book1/assets/` directory,
add the `images_base_dir` option to the appropriate edition:

```yml
# config.yml
book:
    # ...

    editions:
        website:
            format:          html_chunked
            images_base_dir: /images/books/book1/assets/
            # ...
```

## Upgrade to easybook 4.8 ##

### Introduced a new highlight cache ###

If you write technical books about any programming language, you can now reduce
book publishing time very significantly. **easybook** now supports `highlight_cache`
option for any book edition:

```yaml
book:
    # ...

    print:
        format: pdf
        highlight_cache: true
        highlight_code:  true
        # ...
```

If `true`, this option forces **easybook** to store in the cache any highlighted
code listing. Benchmarks are very promising: publishing a 700-page PDF book with
thousands of highlighted code lines went from 60 seconds to 4 seconds.

## Upgrade to easybook 4.4 ##

### Renamed `parsing_item` to `active_item` ###

Access to the specific item being parsed/decorated/transformed is no longer
exclusive of the parsing methods. Therefore, `publishing.parsing_item` variable
is renamed to `publishing.active_item` in `Application.php`.

### Slugger ###

The slugger options are now passed to the `slugify()` method instead of the
slugger constructor. This way you can use different slugger options for the same
book.

Before:

```php
$app->get('slugger')->slugify($string1);

$slugger = new Easybook\Util\Slugger($app, '-', '', false);
$slugger->slugify($string2);
```

After:

```php
$app->get('slugger')->slugify($string1);
$app->get('slugger')->slugify($string2, array('unique' => false));
```

Available options:

```php
'separator' => '-'  // used between words and instead of illegal characters
'prefix'    => ''   // prefix to be appended at the beginning of the slug
'unique'    => true // should this slug be unique across the entire book?
```

### New events ###

easybook 4.4 adds two new events:

  * `PRE_DECORATE`, notified just before an item is going to be decorated with
    the appropriate template.
  * `POST_DECORATE`, notified just after an item has been decorated with the 
    appropriate template.

### Updated `BaseEvent` event ###

`BaseEvent` has been modified to include two new getter/setter methods. Now you
don't have to create a new event class to perform basic operations on the active
item:

```php
public function getItem()
{
    return $this->app['publishing.active_item'];
}

public function setItem($item)
{
    $this->app->set('publishing.active_item', $item);
}
```

## Upgrade to easybook 4.2 ##

### 1. Books can now define their own labels and titles for each edition ###

Explained in the *[custom labels and titles](http://easybook-project.org/doc/chapter-2.html#custom-labels-and-titles)* section of the documentation.

### 2. auto_label configuration option no longer exists ###

This option enabled/disabled labels for all the elements types of the book:
chapters, appendices, figures, ...

This option has been replaced by the much more powerful `labels` option,
which defines the element types for which labels are added:

```yml
book:
    # ...
    editions:
        web:
            # no label for any element
            labels:  []
            # ...
        website:
            # labels are only added for chapter and appendices headings
            labels:  ['appendix', 'chapter']
            # ...
        print:
            # besides chapter and appendices headings, labels are also added
            # to tables and images captions
            labels: ['appendix', 'chapter', 'figure`, `table`]
```

`figure` and `table` aren't content types, but special values used only
in `labels` option.

### 3. Default labels for appendices have been modified ###

Appendix labels - before:

```yml
label:
    appendix: ['Appendix {{ item.number }} ', '', '', '', '', '']
```

Appendix labels - after:

```yml
label:
    appendix:
        - 'Appendix {{ item.number }}'
        - '{{ item.counters[0:2]|join(".") }}' # 1.1
        - '{{ item.counters[0:3]|join(".") }}' # 1.1.1
        - '{{ item.counters[0:4]|join(".") }}' # 1.1.1.1
        - '{{ item.counters[0:5]|join(".") }}' # 1.1.1.1.1
        - '{{ item.counters[0:6]|join(".") }}' # 1.1.1.1.1.1
```

### 4. Some label parameters have been renamed ###

The main parameters available for each label are now grouped under `item`
variable. Other special parameters may also exist for some labels, as explained
in the documentation.

Chapter labels - before:

```yml
label:
    chapter:
        - 'Chapter {{ item.number }} '
        - '{{ counters[0:2]|join(".") }}' # 1.1
        - '{{ counters[0:3]|join(".") }}' # 1.1.1
        - '{{ counters[0:4]|join(".") }}' # 1.1.1.1
        - '{{ counters[0:5]|join(".") }}' # 1.1.1.1.1
        - '{{ counters[0:6]|join(".") }}' # 1.1.1.1.1.1
```

Chapter labels - after:

```yml
label:
     chapter:
         - 'Chapter {{ item.number }} '
         - '{{ item.counters[0:2]|join(".") }}' # 1.1
         - '{{ item.counters[0:3]|join(".") }}' # 1.1.1
         - '{{ item.counters[0:4]|join(".") }}' # 1.1.1.1
         - '{{ item.counters[0:5]|join(".") }}' # 1.1.1.1.1
         - '{{ item.counters[0:6]|join(".") }}' # 1.1.1.1.1.1
```

Figure and image labels - before:

```yml
label:
    figure: 'Figure {{ item.number }}.{{ counter }}'
    table:  'Table {{ item.number }}.{{ counter }}'
```

Figure and image labels - after:

```yml
label:
    figure: 'Figure {{ element.number }}.{{ item.number }}'
    table:  'Table {{ element.number }}.{{ item.number }}'
```

### 5. Images are now decorated with their own Twig template ###

```jinja
<div class="figure">
    {{ item.content }}

{% if item.caption != '' %}
    <p class="caption"><strong>{{ item.label }}</strong> {{ item.caption }}</p>
{% endif %}
</div>
```

You can tweak the previous design creating a `figure.twig` template in your
own theme. If you want to maintain the previous no-decoration design, just
create in your book the template `Resources/Templates/figure.twig` with the
following content:

```jinja
{{ item.content }}
```

### 6. Tables are now decorated with their own Twig template ###

```jinja
<div class="table">
{% if item.caption != '' %}
    <p class="caption"><strong>{{ item.label }}</strong> {{ item.caption }}</p>
{% endif %}

    {{ item.content }}
</div>
```

You can tweak the previous design creating a `table.twig` template in your
own theme. If you want to maintain the previous no-decoration design, just
create in your book the template `Resources/Templates/table.twig` with the
following content:

```jinja
{{ item.content }}
```

### 7. It's no longer necessary to define a default content ###

Previous versions of **easybook** required that every content type had a default
content, just in case book config hadn't defined it. This also simplifies the creation
of custom content types.

Now **easybook** only defines sensible default contents for `title`, `license`
and `edition` content type. The other empty defeault files have been deleted.

Documentation has been updated to better explain how to define a custom content type.

### 8. Added eight new content types ###

**easybook** has added eight new content types (`afterword`, `conclusion`, `epilogue`,
`foreword`, `glossary`, `introduction`, `preface`, `prologue`).

This means that you no longer need to define a custom content type if you need any of the
new content types. Moreover, eight new default labels, titles and templates have been defined
for the new contents.

### 9. Added list-of-figures and list-of-tables content types ###

**easybook** has added two new content types: `lof` (list of figures) and `lot` (list of tables).
You can easily add these types on your books:

```yaml
book:
    # ...
    contents:
        # ...
        - { element: lof }
        - { element: lot }
```

`figure.twig` and `table.twig` have been modified to add an `id` attribute necessary for
linking tables and images.

Two new templates have been created: `lof.twig` and `lot.twig` and two new default titles
have been defined for all supported languages.

### 10. Code listings are now decorated with their own Twig template ###

```jinja
<div class="code {{ item.language }}">
{{ item.content }}
</div>
```

You can tweak the previous design creating a `code.twig` template in your
own theme. If you want to maintain the previous no-decoration design, just
create in your book the template `Resources/Templates/code.twig` with the
following content:

```jinja
{{ item.content }}
```

### 11. Added syntax highlighting for code listings ###

**easybook** now can highlight any code listing. Just add the programming, markup
or configuration language as the first line of the listing.

Code listing with no syntax highlighting:

```
public function onStart(BaseEvent $event)
{
    $event->app->set('app.timer.start', microtime(true));
}
```

Code listing with syntax highlighting:

```
[php]
public function onStart(BaseEvent $event)
{
    $event->app->set('app.timer.start', microtime(true));
}
```

**easybook** uses the [GeSHi library](http://qbnz.com/highlighter) to highlight
the code listings. Therefore, it supports more than 200 programming languages
​(`[php]`, `[java]`, `[c]`, `[javascript]`, `[ruby]`, `[python]`, `[perl]`,
`[erlang]`, `[haskell]`, ...), markup languages ​​(`[html]`, `[yaml]`, `[xml]`, ...)
and configuration (`[ini]`, `[apache]`, ...).

### 12. Added ePub support ###

Read the updated documentation for details, but in short you can now
define editions of type `epub` which generates `.epub` version 2 books:

```yaml
book:
    # ...
    editions:
        ebook:
            format:         epub
            highlight_code: false
            include_styles: true
            labels:         ['appendix', 'chapter']  # labels also available for: "figure", "table"
            toc:
                deep:       1
                elements:   ["appendix", "chapter", "part"]
```

