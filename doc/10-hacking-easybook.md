# Hacking easybook

## Custom configuration options

**easybook** doesn't restrict the configuration options that you can set on your book, editions and contents. **easybook** always puts you in control, so 
you can define all the new settings that you may need.

Do you want to show the price of the book on the cover? Add a new option called `price` under the `book` option of `config.yml` file:

```yaml
parameters:
    book:
        # ...
        price: 10
```

Now you can use this option in any template with the following expression: 
`{{ book.price }}`. 

Do you want to use different CSS frameworks to generate the book website? Add a new option called `framework` in some editions:

```yaml
parameters:
    book:
        editions:
            my_website1:
                format: pdf
                framework: bootstrap_3
                # ...
```

This new option is now available in any template through the following expression: `{{ edition.framework }}`.

You can also add new options to the contents of the book.  Do you want to indicate the estimated reading time in each chapter? Add the following `time` 
option for `chapter` elements:

```yaml
parameters:
    book:
        editions:
            my_website1:
                contents:
                    - { element: cover }
                    - { element: chapter, number: 1, ..., time: 20 }
                    - { element: chapter, number: 2, ..., time: 12 }
                    - ...
```

And now you can add `{{ item.config.time }}` in `chapter.twig` template to show the estimated reading time for each chapter.

Book configuration options can also use Twig expressions for their values (even for dynamic options set by `--configuration` option explained in the next section):

```yaml
parameters:
    book:
        title:            "{{ book.author }} diary"
        author:           "..."
        publication_date: "{{ '+2days'|date('m/d/Y') }}"
        # ...
```

## Configuring Parameters

In addition to its own configuration options, a book can also modify global **easybook** parameters. To do so, add an `easybook` configuration section inside your `config.yml` file (it's recommended to create it at the top of the file to spot it easily):

```yaml
parameters:
    kindlegen_path: '/path/to/utils/KindleGen/kindlegen'

    book:
        title: '...'
```

Define the **easybook** global options under the `parameters` key. In the previous example, the book sets the `kindlegen.command_options`
option to tweak the command used to generate Kindle-compatible MOBI files. In addition, to avoid repeating it each time the book is generated, the book also sets the path to the `kindlegen` library thanks to the `kindlegen.path`.

You can replace any of the parameters defined or used in the `Application.php` class located at `src/Easybook/DependencyInjection/`. Therefore, use the
following configuration to modify the directory where the book is generated: 

```yaml
parameters:
    book:
        title: '...'
    # ...
```

The separator used by the slugger is another option that is usually modified. By default **easybook** uses the dash (`-`) for the slugs (this affects the
book page names and the URL for the books published as websites). If you prefer the underscore (`_`) you can now easily configure it in your book:

```yaml
parameters:
    book_title: '...'
    # ...
```

## Defining new content types ## {#new-content-types}

It's uncommon and generally unneeded, but you can also define new content types besides the 21 types included in **easybook**. Imagine that you want to include
a page with a humorous cartoon between some chapters. Let's call this new content type `cartoon`.

If pages of type `cartoon` include few contents (a picture and its caption for example), it's better to define these contents directly in the  `config.yml` 
file:

```yaml
contents:
    - { element: cover }
    - ...
    - { element: chapter, number: 1, ... }
    - { element: cartoon, image: cartoon1.png, caption: '...' }
    - { element: chapter, number: 2, ... }
    - ...
```

Then, create a custom `cartoon.twig` template in the `Resources/Templates/` directory of your book:

``` .twig
<div class="page:cartoon">
    <img src="{{ item.config.image }}" />
    <p>{{ item.config.caption }}</p>
</div>
```

In contrast, if pages of type `cartoon` include a lot of contents, it's better to create a content file for each of these elements:

```yaml
contents:
    - { element: cover }
    - ...
    - { element: chapter, number: 1, ... }
    - { element: cartoon, content: cartoon1.md }
    - { element: chapter, number: 2, ... }
    - ...
```

Then, display this content with the following simplified `cartoon.twig` template:

``` .twig
<div class="page:cartoon">
    {{ item.content }}
</div>
```

Finally, you can also combine these two methods creating a file with the contents and adding several configuration options in `config.yml`. The 
template can easily use both sources of information:

``` .twig
<div class="page:cartoon">
    <img src="{{ item.config.image }}" />

    {{ item.content }}
</div>
```

That's it! You can now use the new `cartoon` content type in your book and you can create new content types following the same steps explained above.

## easybook internals ##

**easybook** flexibility allows you to create advanced books without effort and without having to study the source code of the application. However, to master
**easybook** you'll have to dive into the guts of the application.

The most important class of **easybook** is
`src/Easybook/DependencyInjection/EasybookKernel.php`. This class follows the
*dependency injection* pattern, uses the [Symfony\DependencyInjection](http://github.com/symfony/dependencyinjection) component and contains 
all the variables, functions and services of the application.

The most interesting command of **easybook** is `publish`, which publishes an specific edition of the book. Internally it uses a `*Publisher` class which 
depends on the type of edition that is published (`epub` or `pdf`). The details of each *publisher* vary, but the basic
workflow is always the same:

```php
public function publishBook()
{
    $this->loadContents();
    $this->parseContents();
    $this->decorateContents();
    $this->assembleBook();
}
```

First, the contents of the book defined in the `contents` option of the
`config.yml` file are loaded (`loadContents()`) . Then, each content is parsed (`parseContents()`) to convert it from its original format to the 
format needed by this publisher.

At the moment **easybook** only supports Markdown as the original format. If you want to add support for other formats, you have to create a new parser (
dig into the Markdown parser to know how to create it) and you also have to change the `parseContents()` method of the publisher.

After converting all contents to the desired format (usually HTML) they are decorated using Twig templates (`decorateContents()`). Finally, the
`assembleBook()` method is responsible for creating the published book. This is the most unique method of the publishers, as sometimes it has to create a PDF
file, sometimes just an HTML file and other times it must create an entire website with many HTML pages.
