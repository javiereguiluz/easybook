# Editions # {#editions}

**easybook** allows you to publish the very same book in radically different 
ways. This is possible thanks to the **editions**, that define the specific 
characteristics of the published book.

Editions are defined under the `editions` option in the `config.yml` file. By
default, the books created with the `new` command define five editions named
`ebook`, `kindle`, `print`, `web` and `website`:

```yaml
book:
    # ...
    editions:
        ebook:
            format:  epub
            # ...

        kindle:
            extends: ebook
            format:  mobi

        print:
            format:  pdf
            # ...
```

## Edition formats ##

A single book can define as many editions as needed. The only requirement is
that the name of each edition must be unique for the same book and cannot contain white spaces.

Each edition is published at the `Output/` directory of the book, in a
subdirectory named after the edition name. You can define any number of 
editions, but all of them must belong to one of the following five types 
defined by the `format` option:

  * `epub`, the book is published as an e-book named `book.epub`.
  * `mobi`, the book is published as a Kindle-compatible e-book named
    `book.mobi`.
  * `pdf`, the book is published as a PDF file named `book.pdf`.

## Configuration options ##

The purpose of the editions is to define a set of unique characteristics for 
the published book. This is done with the several configuration options defined
by **easybook** for the editions. Some of these options are common for every
edition format and others are specific to each format.

### Common configuration options ### {#common-edition-options}

  * `include_styles`, if `true` **easybook** default CSS styles are applied to
    the published book. If you want to fully customize the design of your 
    book, don't apply these styles.
  * `labels`, sets the content types for which **easybook** will add labels
    to their section headings. By default labels are only added to headings of
    chapters and appendices. In addition to the regular content types, you can
    use two special values called `figure` and `table` to add labels for book
    images and tables. If you don't want to show labels in your book, set an
    empty value for this option: `labels: ~`.
  * `toc`, sets the options of the table of contents. It's ignored unless the
    book has at least one `toc` element type. It has two options:
    * `deep`, the maximum heading level included in the TOC (`1` is the lowest
      possible number and would only show `<h1>` level headings; `6` is the
      highest possible value and would show all `<h1>`, `<h2>`, `<h3>`, `<h4>`,
      `<h5>` and `<h6>` headings).
    * `elements`, the type of elements included in the TOC (by default, only
      `appendix`, `chapter` and `part` are included).

### Format specific configuration options ###

In addition to the common configuration options, some formats define their own
specific configuration options:

  * [Configuration options for the `pdf` format](#pdf-configuration-options)

## Before and after scripts ##

Publishing a digital book usually involves more than the actual publication
of the book. Sometimes, before publishing the book, its contents must be
updated by downloading them from a remote server. After the publication, you
may need to copy the book into another directory or you may have to notify 
some other system about the publication.

**easybook** simplifies all these tasks with the `before_publish` and
`after_publish` edition options. These options are modeled after the popular
[`before_script` and `after_script` Travis CI options][1] and allow you to
execute commands before or after the book publication without the need to
define a custom plugin or a script.

Each option admit an array of commands that are executed sequentially:

```yaml
book:
    title: '...'
    # ...

    editions:
        # ...

        website:
            chunk_level: 2
            # ...

            before_publish:
                - echo "This command is executed before book publishing"
                - git pull ...
                - cp ...

            after_publish:
                - "/home/user/scripts/notify_book_publish.sh"
                - "/home/user/scripts/update_google_sitemap_xml.sh"
```

If you only need to execute one command, you can replace the array for
a simple string:

```yaml
book:
    # ...

    editions:

        website:
            # ...
            before_publish: "cd ... && git pull"
            after_publish:  "/home/user/scripts/notify_book_publish.sh"
```

As any other **easybook** command, all of these scripts are rendered as Twig
strings, so they can easily access to any application property:

```yaml
book:
    # ...

    editions:

        website:
            # ...
            before_publish: "git clone git://github.com/{{ app.get('book_slug') }}/book"
            after_publish:  "cp ... /home/{{ book.author }}/books/{{ 'now'|date('YmdHis') }}_book.pdf"
```
