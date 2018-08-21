# Publishing MOBI books #

MOBI books published with **easybook** are compatible with the Kindle e-book 
readers.

## Requirements ##

**easybook** uses a free third-party tool called `Kindlegen` to generate MOBI 
books. For that reason, before publishing any MOBI book, download `Kindlegen`
for Windows, Mac OS X or Linux at [amzn.to/kindlegen][1].

Once installed, execute the `kindlegen` command without any argument to display
the help of the application and to check that everything went fine:

~~~ .cli
$ kindlegen

*************************************************************
 Amazon kindlegen V2.9 build 0523-9bd8a95
 A command line e-book compiler
 Copyright Amazon.com and its Affiliates 2013
*************************************************************

Usage : kindlegen [filename.opf/.htm/.html/.epub/.zip or directory]
        [-c0 or -c1 or c2] [-verbose] [-western] [-o <file name>]
Options:
   -c0: no compression
   -c1: standard DOC compression
   -c2: Kindle huffdic compression

   -o <file name>: Specifies the output file name. Output file will
      be created in the same directory as that of input file.
      <file name> should not contain directory path.

   -verbose: provides more information during ebook conversion

   -western: force build of Windows-1252 book

   -releasenotes: display release notes

   -gif: images are converted to GIF format (no JPEG in the book)

   -locale <locale option> : To display messages in selected language
~~~

### Configuration ###

When publishing a `mobi` book, **easybook** looks for the `kindlegen` tool in 
the most common installation directories depending on the operating system. If
`kindlegen` isn't found, **easybook** will ask you to type the absolute path
where you installed it:

~~~ .cli
$ ./book publish easybook-doc-en kindle

 Publishing 'kindle' edition of 'easybook documentation' book...

 In order to generate MOBI ebooks, KindleGen library must be installed.

 We couldn't find KindleGen executable in any of the following directories:
   -> /usr/local/bin/kindlegen
   -> /usr/bin/kindlegen
   -> c:\KindleGen\kindlegen

 If you haven't installed it yet, you can download it freely from
 Amazon at: http://amzn.to/kindlegen

 If you have installed it in a custom directory, please type its
 full absolute path:
 > _
~~~

In order to avoid typing the `kindlegen` path every time you publish a book,
you can leverage the **easybook** [global parameters](#global-parameters) to
set the `kindlegen` path once for each book:

~~~ .yaml
easybook:
    parameters:
        kindlegen.path: '/path/to/utils/KindleGen/kindlegen'

book:
    title: '...'
    # ...
~~~

In addition to the `kindlegen` path, you can also tweak the options of the
`kindlegen` command with the `kindlegen.command_options` global parameter:

~~~ .yaml
easybook:
    parameters:
        kindlegen.path: '/path/to/utils/KindleGen/kindlegen'
        kindlegen.command_options: '-c0 -gif verbose'

book:
    title: '...'
    # ...
~~~

## Templates ##

The `mobi` edition format defines the same ten templates of the `epub` format.
The reason is that `mobi` books are first generated as `epub` books and then
transformed into `mobi` books using the `kindlegen` tool:

  * `code.twig`, `figure.twig`, `chunk.twig`, `layout.twig` and `table.twig` 
    are the same templates and have the same meaning as for the `html_chunked` 
    edition.
  * `cover.twig`, the template used to generate the book cover, which can also
    use your own image, as explained in the next sections.
  * `content.opf.twig`, the template that generates `content.opf` file listing
    all the book contents.
  * `toc.ncx.twig`, the template that generates the `toc.ncx` file for the book
    table of contents.
  * `container.xml.twig` y `mimetype.twig`, templates that generate other minor
    but necessary files for `.epub` format books.

[1]: http://amzn.to/kindlegen
