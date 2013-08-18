# Publishing your first book #

**easybook** is an application that lets you easily publish books in various
electronic formats. Although it was originally designed to publish programming 
books, you can use **easyboook** to publish any kind of book, manual or 
documentation website.

![easybook workflow diagram](what_is_easybook.png)

**easybook** is currently designed for authors with advanced computer skills 
or companies with IT departments or personnel. For that reason, **easybook** 
lacks a GUI interface and it can only be used from the command line. 

**easybook** is also a free and open-source application published under the
[MIT license][1]. This means that you can do *almost* anything with it. The 
only condition is that if you use **easybook** in your applications, you must 
always maintain the original `LICENSE.md` file included in the source code of 
**easybook**. This file explains the license of the application and its 
original author.

## Installing easybook ##

Before installing **easybook**, make sure that you have PHP 5.3.2 or higher 
installed on your computer. This is the only technical requisite to install 
and use **easybook**. You can check your installed PHP version executing the 
following command on the console:

~~~ .cli
$ php -v
~~~

The recommended method to install **easybook** is to use [Composer][2]:

~~~ .cli
$ php composer.phar create-project easybook/easybook <installation-dir>
~~~

Replace the `<installation-dir>` with the path where you want **easybook**
to be installed and you are done. If Composer isn't installed on your computer,
you can install it executing the following command:

~~~ .cli
$ curl -s http://getcomposer.org/installer | php
~~~

Once installed, check that everything is correct by executing the `./book`
script inside the **easybook** installation directory. If you see the list of
available commands, everything went fine:

~~~ .cli
$ cd <easybook-installation-dir>
$ ./book

                     |              |
 ,---.,---.,---.,   .|---.,---.,---.|__/
 |---',---|`---.|   ||   ||   ||   ||  \
 `---'`---^`---'`---|`---'`---'`---'`   `
                `---'

easybook is the easiest and fastest tool to generate
technical documentation, books, manuals and websites.

Available commands:
  benchmark   Benchmarks the performance of book publishing
  customize   Eases the customization of the book design
  help        Displays help for a command
  list        Lists commands
  new         Creates a new empty book
  publish     Publishes an edition of a book
  version     Shows installed easybook version
~~~

N> If the `./book` script doesn't work, try `php book` or check the execution
N> permissions of the `book` script.

The `./book` script is the unique *entry point* for every **easybook**
command. If you need to know for example the installed version, just run the
`version` command through the `book` script:

~~~ .cli
$ ./book version

                    |              |    
,---.,---.,---.,   .|---.,---.,---.|__/ 
|---',---|`---.|   ||   ||   ||   ||  \ 
`---'`---^`---'`---|`---'`---'`---'`   `
               `---'

easybook installed version: 5.0
~~~

### Alternative installation methods ###

If you just want to test drive **easybook**, download the [easybook.zip][3] 
file, uncompress it somewhere and you are done.

If you are an advanced user that want to *hack* or modify **easybook**, clone its repository with Git and install its dependencies with Composer:

~~~ .cli
$ mkdir easybook
$ git clone http://github.com/javiereguiluz/easybook.git easybook

// download vendors and dependencies
$ cd easybook
$ php composer.phar install
~~~

## Publishing the first book ##

Before creating your first book in [the next section](#creating-a-new-book), 
let's dig into book publication using the **easybook** documentation as the 
sample book. This book is located at the `doc/easybook-doc-en/` directory and 
it's a good resource for learning how to create advanced books with
**easybook**.

In order to transform the original Markdown contents of the documentation into
a real book, execute the following command:

~~~ .cli
$ cd <installation-dir>
$ ./book publish easybook-doc-en web
~~~

The first argument of the `publish` command is the name of the directory where
the book contents are stored (`easybook-doc-en`) and the second argument is the
name of the `edition` to be published. After executing this command, you 
should see the following output:

~~~ .cli
$ ./book publish easybook-doc-en web

                     |              |
 ,---.,---.,---.,   .|---.,---.,---.|__/
 |---',---|`---.|   ||   ||   ||   ||  \
 `---'`---^`---'`---|`---'`---'`---'`   `
                `---'

 Publishing 'web' edition of 'easybook documentation' book...

 [ OK ]  You can access the book in the following directory:
 <installation-dir>/doc/easybook-doc-en/Output/web

 The publishing process took 0.5 seconds
~~~

The `publish` command always displays the edition (`web`) and the title
(`easybook documentation`) of the book being published. Editions are one of the
most powerful **easybook** features and they are thoroughly explained in
[their own chapter](#editions). For now, consider that a single book can be 
published in a lot of different ways and formats, each of one being an 
edition.

In case of error, the output of the `publish` command also displays the error
cause and most of the times,how to solve it. If everything went fine, the
`publish` command displays the path of the directory where that book edition
was published (`<installation-dir>/doc/easybook-doc-en/Output/web`) and the
total time elapsed to publish the book (`0.5 seconds`).

Browse to the `<installation-dir>/doc/easybook-doc-en/Output/web` directory and
you'll find an HTML file called `book.html`. If you open it with a browser,
you'll see the full **easybook** documentation published as a single HTML page.

Books published as single HTML pages aren't very useful, so execute now the
following command to publish the book as a website:

~~~ .cli
$ ./book publish easybook-doc-en website
~~~

If you browse to the `<installation-dir>/doc/easybook-doc-en/Output/website` 
directory, you'll find a directory called `book/` with a lot of HTML files 
inside it. As you can see, just by publishing another edition (`website`
instead of `web`) the same book is published in a totally different way.

Execute once again the `publish` command but using the `ebook` edition:

~~~ .cli
$ ./book publish easybook-doc-en ebook
~~~

As you surely have guessed, the book is now published as a `book.epub` file
inside the `<installation-dir>/doc/easybook-doc-en/Output/ebook` directory. Now
you can copy this `book.epub` file into any `.ePub` compatible reader (iPad 
tablets, iPhone phones, most Android tablets and phones and every e-book 
reader except Amazon Kindle) and start reading the **easybook** documentation 
as an e-book.

Similarly, you can publish your book as `MOBI` (the format for Kindle-
compatible e-books) and `PDF` files. However, these formats require the 
installation of two libraries and they will be explained in the next chapters:

~~~ .cli
// publishes the book as a PDF file
$ ./book publish easybook-doc-en print

// publishes the book as a Kindle-compatible MOBI e-book
$ ./book publish easybook-doc-en kindle
~~~

N> For those impatient readers that cannot wait to try the `MOBI` and `PDF`
N> publication, these are the third-party libraries needed to generate those
N> formats:
N> 
N>   * For `MOBI` books: [KindleGen](http://amzn.to/kindlegen)
N>   * For `PDF` books: [PrinceXML](http://www.princexml.com/)

## Creating a new book ## {#creating-a-new-book}

In this section you will create and publish your own book. **easybook** 
requires that your books follow a certain structure of files and directories. 
To avoid creating this structure by hand, books are bootstrapped with the `new`
command:

~~~ .cli
$ ./book new "My First Book"
~~~

The only argument required by the `new` command is the title of the book 
enclosed with quotes. After executing the previous command, **easybook** will 
create a new directory called `my-first-book` inside the **easybook** `doc/` 
directory, and with the following file and directory structure:

~~~
<easybook>/
    doc/
        my-first-book/
            config.yml
            Contents/
                chapter1.md
                chapter2.md
                images/
            Output/
~~~

These are the files and directories created by **easybook**:

  * `config.yml`, this file contains all the book configuration options.
    We'll cover all of them in the next chapters, but meanwhile you can change 
    the `author` option to set your name as the book author's name.
  * `Contents/`, this directory stores all the book contents (both text and
    images). **easybook** creates two sample chapters (`chapter1.md` and
    `chapter2.md`) and an empty `images/` directory.
  * `Output/`, initially this directory is empty, but eventually it will 
    contain the published book.

### Writing the book ###

Book contents are written using regular text files with the Markdown syntax.
This format has become the *de facto* standard for writing documentation for
Internet. If you are not familiar with the Markdown syntax, read the
[Markdown reference](#markdown-reference) appendix.

N> Markdown is the only format currently supported by **easybook**. In the
N> future, more formats will be supported, such as reStructuredText and
N> Textile.

Therefore, forget **easybook** for a while and write the contents of your
book in Markdown syntax using your favorite text editor (`vi`, *Notepad*,
*TextMate*, *SublimeText*, etc.)

The `config.yml` file lists all the book items, such as the chapters and 
appendices. The sample book created with the `new` command only includes two
chapters. Therefore, whenever you add a new chapter to the book, don't forget 
to add it to the `config.yml` file too:

~~~ .yaml
book:
    # ...
    contents:
        - { element: cover }
        - { element: toc   }
        - { element: chapter, number: 1, content: chapter1.md }
        - { element: chapter, number: 2, content: chapter2.md }
        - { element: chapter, number: 3, content: chapter3.md }
        - { element: chapter, number: 4, content: chapter4.md }
        - { element: chapter, number: 5, content: chapter5.md }
        - ...
~~~

The next chapter explains in detail all the configuration options of this file,
and the 21 different [content types](#content-types) that **easybook** books
can include.

As the book publication process is extremely fast (less than 2 seconds for a
400-page book on an average computer), you can publish every edition of the
book periodically to check your progress:

~~~ .cli
$ ./book publish my-first-book web
$ ./book publish my-first-book website
$ ./book publish my-first-book ebook
$ ./book publish my-first-book kindle
$ ./book publish my-first-book print
~~~

[1]: http://opensource.org/licenses/MIT
[2]: http://getcomposer.org/
[3]: https://github.com/javiereguiluz/easybook-package/blob/master/easybook.zip?raw=true
