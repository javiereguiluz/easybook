# Publishing PDF books #

**easybook** can also publish books as high-quality and full-featured PDF 
files.

## Requirements ##

**easybook** uses a third-party tool called `PrinceXML` to generate PDF books. 
For that reason, before publishing any PDF book, download a fully-functional
demo version of `PrinceXML` for Windows, Mac OS X or Linux at
[princexml.com](http://www.princexml.com/).

Once installed, execute the `prince` command without any argument to display
the help of the application and to check that everything went fine:

~~~ .cli
$ prince
Usage:
  prince [OPTIONS] file.xml              Convert file.xml to file.pdf
  prince [OPTIONS] doc.html -o out.pdf   Convert doc.html to out.pdf
  prince [OPTIONS] FILES... -o out.pdf   Combine multiple files to out.pdf

Try 'prince --help' for more information.
~~~

### Configuration ###

When publishing a `pdf` book, **easybook** looks for the `PrinceXML` tool in 
the most common installation directories depending on the operating system. If
`PrinceXML` isn't found, **easybook** will ask you to type the absolute path
where you installed it:

~~~ .cli
$ ./book publish easybook-doc-en print

 Publishing 'print' edition of 'easybook documentation' book...

 In order to generate PDF files, PrinceXML library must be installed.

 We couldn't find PrinceXML executable in any of the following directories:
   -> /usr/local/bin/prince
   -> /usr/bin/prince
   -> C:\Program Files\Prince\engine\bin\prince.exe

 If you haven't installed it yet, you can download a fully-functional
 demo at: http://www.princexml.com/download

 If you have installed in a custom directory, please type its full
 absolute path:
 > _
~~~

In order to avoid typing the `PrinceXML` path every time you publish a book,
you can leverage the **easybook** [global parameters](#global-parameters) to
set the `PrinceXML` path once for each book:

~~~ .yaml
easybook:
    parameters:
        prince.path: '/path/to/utils/PrinceXML/prince'

book:
    title:  '...'
    # ...
~~~

## Templates ##

The following list shows all the templates used by `pdf` format editions to
publish the book. These are also the templates that you can override 
[using your own templates](#custom-templates):

  * A template for each of the 21 content types: `acknowledgement.twig`,
    `afterword.twig`, `appendix.twig`, `author.twig`, `chapter.twig`,
    `conclusion.twig`, `cover.twig`, `dedication.twig`, `edition.twig`,
    `epilogue.twig`, `foreword.twig`, `glossary.twig`, `introduction.twig`,
    `license.twig`, `lof` (list of figures), `lot` (list of tables),
    `part.twig`, `preface.twig`, `prologue.twig`, `title.twig` and `toc.twig`
    (table of contents).
  * `book.twig`, this is the final template that assembles all the book
    contents.
  * `code.twig`, the template used to decorate all the code listings included 
    in the book.
  * `figure.twig`, the template used to decorate all the images included in the
    book.
  * `table.twig`, the template used to decorate all the tables included in the
    book.

## Configuration options ## {#pdf-configuration-options}

Besides the [common configuration options](#common-edition-options), `pdf`
editions can also set any of the following specific options:

  * `isbn`, the ISBN-10 or ISBN-13 code of the book.
  * `margin`, sets the four margins of the printed book: `top`, `bottom`,
    `inner` and `outter`. If the book is one-sided, `inner` equals left margin 
    and `outter` equals right margin. The values of the margins can be set 
    with any CSS valid length unit (`1in`, `25mm`, `2.5cm`).
  * `page_size`, the page size of the printed book. Instead of setting the page
    dimensions, **easybook** uses [named page sizes][1] such as `US-Letter`,
    `US-Legal`, `crown-quarto`, `A4`, `A3`, etc.
  * `two_sided`, if `true` the PDF file is formatted for two-sided printing, 
    which means that some blank pages will probably be inserted to ensure that
    the important contents always start at an odd page. If this option is set
    to `false`, no blank pages will be inserted.

## Book cover ##

Similarly to ePub books, **easybook** allows PDF books to override the 
automatically generated text-based cover. If you want to use your 
very own cover image for the `pdf` books, just create a `cover.pdf` file 
and copy it into one of these directories: (they are listed by priority, 
meaning that the first `cover.pdf` file found is used):

  1. `<book>/Resources/Templates/<edition-name>/cover.pdf`, to use the cover
     only for this specific `<edition-name>` edition.
  2. `<book>/Resources/Templates/pdf/cover.pdf`, to use the same cover for
     every `pdf` edition.
  3. `<book>/Resources/Templates/cover.pdf`, produces the same result as the
     previous option.

The first option is useful when you need to use different covers for
different PDF editions of your book (e.g. high quality cover for printing
the book, medium quality cover for distributing the book via web, etc.)

[1]: http://www.princexml.com/doc/9.0/page-size/
