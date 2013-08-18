# Publishing HTML books #

HTML books can be published either as a single HTML page (`html` format) or as 
a complete website (`html_chunked` format). Besides the common templates and 
configuration options explained previously, this chapter explains the custom
templates and configuration options that HTML books can use.

## Templates ## {#html-templates}

The following list shows all the templates used by `html` format editions to
publish the book. These are also the templates that you can override 
[using your own templates](#custom-templates):

  * A template for each of the 21 content types: `acknowledgement.twig`,
    `afterword.twig`, `appendix.twig`, `author.twig`, `chapter.twig`,
    `conclusion.twig`, `cover.twig`, `dedication.twig`, `edition.twig`,
    `epilogue.twig`, `foreword.twig`, `glossary.twig`, `introduction.twig`,
    `license.twig`, `lof` (list of figures), `lot` (list of tables),
    `part.twig`, `preface.twig`, `prologue.twig`, `title.twig` and `toc.twig`
    (table of contents).
  * `book.twig`, this is the template that ultimately assembles all the book
    contents.
  * `code.twig`, the template used to decorate all the code listings included 
    in the book.
  * `figure.twig`, the template used to decorate all the images included in the
    book.
  * `table.twig`, the template used to decorate all the tables included in the
    book.

The `html_chunked` format editions don't use a different template for each
content type. They only define the following five templates, which you can
also override [using your own templates](#custom-templates):

  * `code.twig`, the template used to decorate all the code listings included 
    in the book.
  * `figure.twig`, the template used to decorate all the images included in the
    book.
  * `table.twig`, the template used to decorate all the tables included in the
    book.
  * `index.twig`, the template that generates the front page of the website. It
    displayes the book title, the author name and the full table of contents.
  * `chunk.twig`, generic template applied to all the the content types. This 
    template must only include the contents of the item being decorated. The 
    common features of the website are defined in the `layout.twig` template.
  * `layout.twig`, generic template applied to all the content types, including
    the front page, after decorating them with their own templates. This is the
    best template to include the common features of the website, such as 
    header, footer and links to CSS and JavaScript files.

## Configuration options ## {#html-configuration-options}

Besides the common configuration options, the `html_chunked` editions define
the following custom options:

  * `chunk_level`, the level of the heading used to *chunk* or split the book
    into HTML pages. Its default value is `1`, meaning that each `<h1>` heading
    produce a new HTML page. In other words, the published website will have a
    single HTML page for each chapter, appendix, etc. When the book chapters 
    are long, it's recommended to set the `chunk_level` option to `2`, meaning
    that each `<h1>` and `<h2>` will produce their own HTML page. In other 
    words, each chapter section will be published in its own page, which is 
    much better for long chapters. Currently, **easybook** only supports `1` 
    and `2` level chunking.
  * `images_base_dir`, the prefix used in every image URI. Its default value is
    `images/`, meaning that when you include an image in your book with
    `![...](image1.png)`, the published book will transform it to
    `<img src="images/image1.png" />`. When you embed a published book into 
    another website, this path may no longer work, depending on your 
    configuration. In those cases, use this option to set the correct prefix.
    For example, if you use `images_base_dir: '/img/doc/en'`, the previous
    image will be transformed into: `<img src="/img/doc/en/image1.png" />`

## Syntax highlighting ##

If you use **easybook** to write programming books or technical documentation, 
you can automatically highlight the code listings. There is nothing to install
or configure, because **easybook** already includes the [GeSHi library][1] to 
highlight the code listings. This library highlights more than 200 programming 
languages (`php`, `java`, `c`, `javascript`, `ruby`, `python`,
`perl`, `erlang`, `haskell`, ...), markup languages ​​(`html`, `yaml`, 
`xml`,...) and configuration files (`ini`, `apache`, ...).

### Configuration ###

Syntax highlighting is disabled by default. Set the `highlight_code` option to 
`true` in any edition you want to highlight:

~~~ .yaml
book:
    # ...
    editions:
        edition1:
            highlight_code: true
            # ...

         edition2:
            highlight_code: true
            # ...

         edition3:
            highlight_code: false
            # ...
~~~

### Code block types ###

As developers cannot agree on what code block style should be used,
**easybook** supports the three most used code block types: 1) Markdown
classic, 2) Fenced, 3) GitHub.

**1)** This is the default type and it's based on the traditional 4-spaces or 
tab indentation of code blocks. The difference is that **easybook** augments
this format to allow you set the programming language of the code with a
special tag in the first line of the code listing:

~~~
    [php]
    $lorem = 'ipsum';
    // ...
~~~

When the first line of the code block is the name of a programming or 
configuration language enclosed with brackets (`[` and `]`), this special tag 
is striped from the listing and the rest of the code is appropriately 
highlighted.

If some code listing is language agnostic or you don't want to highlight it, 
don't include any language tag or if you prefer it, add the generic `[code]` 
tag:

~~~
    [code]
    // This code won't be highlighted
    // ...
~~~

The problem with this code block type is that all the code must be indented
and therefore, there cannot be empty lines without leading tabs or
white spaces. This is a **huge problem** with editors that remove tabs and
white spaces in empty lines.

This code will be wrongly parsed:

~~~
    [php]
    $lorem = 'ipsum';
(no spaces or tabs in this line)
    $another_lipsum = 'ipsum';
    // ...
~~~

The same code, with tabs or white spaces in every line, works perfectly:

~~~
    [php]
    $lorem = 'ipsum';
    (4 white spaces in this line)
    $another_lipsum = 'ipsum';
    // ...
~~~

The other two code block types work perfectly whatever the code you write, so
you don't have to mess around with leading tabs or white spaces.

**2)** `fenced`, this is the style defined by the [PHP Markdown library][2]:

  * Define the start of the code block with at least three `~~~`
  * Optionally set the programming language name, prefixing it with a dot.
  * Include the code without any indentation.
  * Define the end of the code block using the same number of `~~~` as the 
    opening of the block.

Examples:

~~~
 ~~~ .php
 $lorem = 'ipsum';
 // ...
 ~~~
~~~

~~~
 ~~~~~~~~~~~~~~~~~~~~~~ .php
 $lorem = 'ipsum';
 // ...
 ~~~~~~~~~~~~~~~~~~~~~~
~~~

~~~
 ~~~
 // some generic code
 // without any programming language
 // ...
 ~~~
~~~

**3)** `github`, this is the style used by GitHub and it's very similar
to the fenced style. Instead of three tildes (`~~~`), use three backticks:

~~~
```php
$lorem = 'ipsum';
// ...
```
~~~

~~~
```
// some generic code
// without any programming language
// ...
```
~~~

You can use any code block type, but a given book can only use one type for all
its code listings. The Markdown classic code block type is enabled by default.
To enable one of the other two code block types, use the `code_block_type` 
global parameter:

~~~ .yaml
easybook:
    parameters:
        parser.options:
            code_block_type: fenced
            # alternatively, you can also use:
            # code_block_type: github

book:
    title:  ...
    # ...
~~~

### Improve the syntax highlighting performance ###

Syntax highlighting is a very slow and time consuming process for books with
hundreds to thousands lines of code. In order to improve its performance,
**easybook** includes an internal highlight cache.

This cache is disabled by default, because it's not useful for books with a 
small number of lines of code. However, for large books it can reduce more than
90% the publication time.

Set the `highlight_cache` option to `true` in any edition you want to enable
this cache:

~~~ .yaml
book:
    # ...
    editions:
        edition1:
            highlight_code:  true
            highlight_cache: true
            # ...
~~~

[1]: http://qbnz.com/highlighter
[2]: http://michelf.ca/projects/php-markdown/
