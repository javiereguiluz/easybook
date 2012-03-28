# Publishing your first book #

**easybook** is an application that lets you easily publish books in various
electronic formats. Originally it was designed to publish technical
programming books, but you can use **easyboook** to publish novels, manuals,
technical books and websites with your projects documentation.

Before using **easybook** check that you have installed and configured
PHP 5.3.2 or higher on your computer. If you do not understand the previous
sentence, ask advice from a computer savvy friend and send him the link to
this page. You can check your installed PHP version executing the following
command on the console:

    [cli]
    php -v

**easybook** is a free and open-source application published under the MIT
license. This means that you can do anything with it. The only condition is
that you must always maintain the `LICENSE.md` file included in the source
code. This file explains the license of **easybook** and its original author.

In any case, you are the sole proprietor of the books published with
**easybook**, including all the copyright and related rights applicable in
your country of residence. You are not obliged to share these works in any
way, even if you benefit from them financially.

## Downloading easybook ##

Before downloading **easybook**, create a directory for it. For example:
  
  * On Windows: `C:\Users\javier\projects\easybook`
  * On Mac OS X: `/Users/javier/projects/easybook`
  * On Linux: `/home/javier/projects/easybook`

If you have Git installed on your computer, you should download  **easybook**
cloning its public repository with the following command (replace `<dir>` for
the path of the directory created previously):

    [cli]
    $ git clone http://github.com/javiereguiluz/easybook.git <dir>

If you don't use Git, you can download **easybook** as a compressed `.zip`
archive. It's not as cool as using Git, but it works fine. Download the
following file and uncompress it on the previously created directory:
<http://github.com/javiereguiluz/easybook/zipball/master>

### Using easybook ###

Once downloaded/unzipped, open a command console and run the following PHP
script to check that you have downloaded **easybook** correctly:

    [cli]
    $ ./book

If everything is fine, **easybook** should welcome you with the following
message:

    [cli]
                        |              |    
    ,---.,---.,---.,   .|---.,---.,---.|__/ 
    |---',---|`---.|   ||   ||   ||   ||  \ 
    `---'`---^`---'`---|`---'`---'`---'`   `
                   `---'

    easybook is the easiest and fastest tool to generate
    technical documentation, books, manuals and websites.

    Available commands:
      help      Displays help for a command
      list      Lists commands
      new       Creates a new empty book
      publish   Publishes an edition of a book
      version   Shows installed easybook version

If you find any issue running `./book` script, try running it as `php book`.
If you still find any issues, check the permissions of the `book` script. If
everything fails, ask again advice from your computer savvy friends.

The `./book` script is the unique *entry point* for every **easybook**
command. If you need to know for example the installed version, just run the
`version` command through `book` script:

    [cli]
    $ ./book version


                        |              |    
    ,---.,---.,---.,   .|---.,---.,---.|__/ 
    |---',---|`---.|   ||   ||   ||   ||  \ 
    `---'`---^`---'`---|`---'`---'`---'`   `
                   `---'

    easybook installed version: 4.2

## Creating the book ##

In this section you will create your first **easybook** sample book. However,
if you want to try **easybook** features as quickly as possible, you can use
ready-made books named `easybook-doc-en` (English **easybook** documentation)
and `easybook-doc-es` (Spanish **easybook** documentation). In that case, you
can skip to the *Publishing the book* section.

**easybook** requires that your books follow a certain structure of archives
and directories. To avoid creating this structure by hand, new books are
bootstrapped with **easybook** `new` command:

    [cli]
    $ ./book new "The Origin of Species"

Type the title of your book after `new` command enclosing it with quotes. The
result of this command is a new `the-origin-of-species` directory inside
**easybook** `doc/` directory. You should see the following structure inside
the new directory:

    <easybook>/
        doc/
            the-origin-of-species/
                config.yml
                Contents/
                    chapter1.md
                    chapter2.md
                    images/
                Output/

These are the archives and directories created by **easybook**:

  * `config.yml`, this archive contains all the book configuration options.
  We'll cover all of them in the next sections and chapters, but meanwhile
  you can change the `author` option to set the book author's name (for
  example, `Charles Darwin`).
  * `Contents/`, this directory holds all the book contents (both text and
  images). **easybook** creates two sample chapters (`chapter1.md` and
  `chapter2.md`) and an empty `images/` directory.
  * `Output/`, initially this directory is empty, but eventually it will 
  contain the published book.

## Writing the book ##

Book contents are written in regular text archives using Markdown syntax.
This format has become the *de facto* standard for writing documentation for
Internet. If you are not familiar with Markdown syntax, read the [official
Markdown syntax reference](http://daringfireball.net/projects/markdown/syntax)
. Markdown is the only format currently supported by **easybook**. In the
future, many more formats will be supported, such as reStructuredText,
Textile and other widely used formats.

Therefore, forget **easybook** for a while and write the contents of your
book in Markdown syntax using your favorite text editor (`vi`, *Notepad*,
*TextMate*, *SublimeText*, etc.)

The structure of a book can be very complex (cover, title page,
acknowledgements, dedication, chapters, parts, etc.). **easybook** supports
all the common book content types, but for now, we'll just focus on the
chapters. You can add as many chapters as you want and each one can be as
large or as short as you need. All you have to do is to list the book
chapters under the `contents` option of the `config.yml` file:

    [yaml]
    book:
        # ...
        contents:
            - { element: cover }
            - { element: toc   }
            - { element: chapter, number: 1, content: chapter1.md }
            - { element: chapter, number: 2, content: chapter2.md }

Each line under the `contents` option defines a content of the book. The
`cover` and `toc` lines are special contents that will be explained later.
Add your book chapters as  `chapter` elements and set their number (with the
`number` option) and the name of the files that hold their contents (with the
`content` option).

Besides being lightning-fast, the main feature of **easybook** is its
flexibility, as it never forces you to work in a certain way. Do you want to
number your chapters in an *imaginative* way? People will think you're crazy,
but **easybook** allows you to do it:

    [yaml]
    book:
        # ...
        contents:
            - { element: cover }
            - { element: toc   }
            - { element: chapter, number: 100, content: chapter1.md }
            - { element: chapter, number: 56,  content: chapter2.md }

Do you need letters instead of numbers? This is usual for appendices instead
of chapters, but **easybook** won't stop you from doing it:

    [yaml]
    book:
        # ...
        contents:
            - { element: cover }
            - { element: toc   }
            - { element: chapter, number: A, content: chapter1.md }
            - { element: chapter, number: B, content: chapter2.md }

You can also use any name for the chapter contents file, as in the following
example that mixes English and Spanish:

    [yaml]
    book:
        # ...
        contents:
            - { element: cover }
            - { element: toc   }
            - { element: chapter, number: 1, content: chapter1.md }
            - { element: chapter, number: 2, content: capitulo2.md }

Using significant names for book content files eases its management:

    [yaml]
    book:
        # ...
        contents:
            - { element: cover }
            - { element: toc   }
            - { element: chapter, number: 1, content: publishing-your-first-book.md }
            - { element: chapter, number: 2, content: publishing-your-second-book.md }

The most important thing about the `contents` option is the order in which
you define the contents. The published book will be always composed of those
contents and in that order. Therefore, the following configuration will
output a book with  the cover between the two chapters and the table of
contents at the very end (completely crazy, but really easy to do with
**easybook**):

    [yaml]
    book:
        # ...
        contents:
            - { element: chapter, number: 1, content: publishing-your-first-book.md }
            - { element: cover }
            - { element: chapter, number: 2, content: publishing-your-second-book.md }
            - { element: toc   }

By default, all content files are stored in the `Contents/` directory.
However, if your book is complex, you can divide the contents into
subdirectories. Then, include the name of the subdirectories in the `content`
option  (don't forget to enclose it with quotes if the file path has spaces):

    [yaml]
    book:
        # ...
        contents:
            - { element: cover }
            - { element: toc   }
            - { element: chapter, number: 1, content: introduction/chapter1.md }
            - { element: chapter, number: 2, content: introduction/chapter2.md }
            - { element: chapter, number: 3, content: advanced/chapter1.md }

The above configuration means that your book contents are distributed this
way:

    <book_dir>/
        ...
        Contents/
            introduction/
                chapter1.md
                chapter2.md
            advanced/
                chapter1.md


## Publishing the book ##

When you finish writing all the chapters and after adding them to the
`config.yml` archive, run the following command to publish the book (replace
`the-origin-of-the-species` by the name of your book directory):

    [cli]
    $ ./book publish the-origin-of-species web

If everything works fine, you should see a new `web` directory inside
`Output/` directory. Enter the `web/` directory and you'll find a file named
`book.html`. This is the complete book in HTML format, ready to publish it on
the Internet.

Then, run the following command:

    [cli]
    $ ./book publish the-origin-of-species website

Now, inside `Output/` directory you'll find a new `website` directory with
several HTML pages. Open `index.html` in your browser and you'll see that
**easybook** has published your book as a fully-functional static website.

Run the following command to generate an e-book:

    [cli]
    $ ./book publish the-origin-of-species ebook

Inside your book's `Output/` directory you'll find a new `ebook` directory
with a file named `book.epub`. This is the e-book version of your book, ready
to be read in any `.ePub` compatible reader (iPad tablets, iPhone phones,
most Android tablets and phones and every e-book reader except Amazon Kindle).

Lastly, run the following command:

    [cli]
    $ ./book publish the-origin-of-species print

Inside `Output/` directory you'll find a new `print` directory which contains
a file name `book.pdf`. Open the file with your PDF reader and you'll see
your book as a beautiful and carefully created PDF ebook. The PDF conversion
is made with an external application named [PrinceXML](http://www.princexml.com/).
If you don't have it installed on your computer, you can download a
fully-functional demo version at <http://www.princexml.com/download/>

**easybook** lets you easily publish the same book in very different ways.
Each of these *ways*  is called **edition**. This concept will be explained
in the next chapter.

## Book configuration options ##

In the previous sections we've mentioned the `author` configuration option.
In fact, **easybook** books can set many more options in the `config.yml` file
. The default values of the options are as follows:

    [yaml]
    book:
        title:            "(the title typed when creating the book)"
        author:           "Change this: Author Name"
        edition:          "First edition"
        language:         en
        publication_date: ~

        generator: { name: easybook, version: 4.2 }

        contents:
            # available content types: acknowledgement, afterword, appendix, author,
            # chapter, conclusion, cover, dedication, edition, epilogue, foreword,
            # glossary, introduction, license, lof (list of figures), lot (list of
            # tables), part, preface, prologue, title, toc (table of contents)
            - { element: cover }
            - { element: toc   }
            - { element: chapter, number: 1, content: chapter1.md }
            - { element: chapter, number: 2, content: chapter2.md }

        editions:
            ebook:
                # (this is a complex option, we'll see it later)
            print:
                # (this is a complex option, we'll see it later)
            web:
                # (this is a complex option, we'll see it later)
            website:
                # (this is a complex option, we'll see it later)

The `contents` and `editions` options are explained in  detail in the
following chapters: `contents` option defines the book contents and their
order; `editions` option defines the features of each edition of the book.

The other options set the basic information of the book:

  * `title`, is the title of the book. By default **easybook** uses the title 
  that you typed when creating the book, but you can change it if needed.
  * `author`, is the name of the book author. If the book has multiple 
  authors, write them all in a row separated by commas:
  `"Name1 Surname1, Name2 Surname2, ..."`
  * `edition`, is the text describing the edition of the book. Normally, you 
  should use *first edition*, *second edition* and so on, but you can 
  describe your book current edition in any way.
  * `language`, the language of the book contents set with a two letter code: 
  `en` for English, `es` for Spanish, `fr` for French, `it` for Italian, `de` 
  for Deutsch, etc.
  * `publication_date`,  the date of publication of the book. The default 
  value is `~`, meaning no publication date. In this case, **easybook** will 
  automatically set the date to the day on which the book is published. If 
  you set a date, enter it in the `month-day-year` format. For example:
  `11-23-2012`.

