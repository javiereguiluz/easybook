# Publishing EPUB books #

EPUB books published with **easybook** are compatible with most e-book readers.
The only notable exception are the Kindle readers, that require MOBI format
ebooks (as explained in the next chapter).

## Templates ##

The following list shows all the templates used by `epub` format editions to
publish the book. These are also the templates that you can override 
[using your own templates](#custom-templates):

  * `code.twig`, `figure.twig`, `chunk.twig`, `layout.twig` and `table.twig` 
    are the same templates and have the same meaning as for the `html_chunked` 
    edition ([see `html_chunked` templates](#html-templates)).
  * `cover.twig`, the template used to generate the book cover when the book
    doesn't include its own cover (see the [book cover section](#epub-cover) 
    bellow).
  * `content.opf.twig`, the template that generates the `content.opf` file 
    listing all the book contents (including its fonts, images and CSS styles).
  * `toc.ncx.twig`, the template that generates the `toc.ncx` file for the book
    table of contents.
  * `container.xml.twig` y `mimetype.twig`, templates that generate other minor
    but necessary files for `.epub` format books. Override these templates only
    if you really know what you're doing, because otherwise, the book won't be
    properly generated.

## Book cover ## {#epub-cover}

**easybook** automatically generates a text-based cover for your book, 
containing both the book title and its author name. If you want to use your 
very own cover image for the `epub` books, just create a `cover.jpg` file 
and copy it into one of these directories:

  1. `<book>/Resources/Templates/<edition-name>/cover.jpg`, to use the image
     only for this specific `<edition-name>` edition.
  2. `<book>/Resources/Templates/epub/cover.jpg`, to use the same cover image
     for every `epub` edition.

The cover image format must be JPEG, because this is the most supported format
in e-book readers. In order to visualize it correctly in advanced readers such
as the iPad, create a large color image (at least 800 pixel height).

## Code highlighting ##

The support of fixed-width fonts in current e-book readers is abysmal. For 
that reason, syntax highlighting doesn't work as expected. The configuration 
options are the same as for any other format (`highlight_code` and
`highlight_cache`) but the actual result depends completely on the e-book 
reader used to read the book.

The following screenshot shows a comparison of the same highlighted code 
displayed in a browser (`html` edition) and in a desktop application to read
e-books (`epub` edition):

![Syntax highlighting in the browser vs an epub application](syntax_highlighting_browser_vs_app.png)

In advanced e-book readers such as the iPad, the code respects the syntax
highlighting, but any highlighted code is displayed in the regular book font,
instead of the fixed-width font of the non-highlighted code:

![Syntax highlighting in the browser vs the iPad](syntax_highlighting_browser_vs_ipad.png)

Considering the actual support for syntax highlighting in e-book readers, it's
strongly recommended to disable the code highlighting for epub editions:

~~~ .yaml
book:
    # ...
    editions:
        ebook:
            format:         epub
            highlight_code: false
            # ...
~~~
