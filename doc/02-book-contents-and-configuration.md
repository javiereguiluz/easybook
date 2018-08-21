# Book contents and configuration #

This chapter explains all the available book configuration options and all the
content types defined by **easybook**.

## Book configuration ##

All the book configuration is centralized in a single file called `config.yml`
at the root directory of the book. Usually this configuration file is divided
into three parts:

~~~ .yaml
book:
    # FIRST part: basic book information
    title:            "..."
    author:           "..."
    edition:          "..."
    language:         "..."
    publication_date: "..."

    # SECOND part: book contents
    contents:
        - ...
        - ...
        - ...

    # THIRD part: book editions
    editions:
        edition1:
            # ...
        edition2:
            # ...
        # ...
~~~

The first part of this file sets the basic book information (enclose the value
of each option with single or double quotes):

~~~ .yaml
book:
    title:            "The Origin of Species"
    author:           "Charles Darwin"
    edition:          "First edition"
    language:         en
    publication_date: "1859-11-24"
~~~

  * `title`, sets the title of the book. By default **easybook** uses the 
    title that you typed when creating the book with the `new` command, but 
    you can change it if needed.
  * `author`, sets the author of the book. If the book has more than one 
    author, separate them with commas (e.g. `James Watson, Francis Crick`).
  * `edition`, sets the name of the current book edition. This is considered
    as the traditional *"literary edition"* and has nothing to do with the
    edition concept explained in the next chapter.
  * `language`, sets the language of the book contents with a two letter code
    (`en` for English, `es` for Spanish, `fr` for French, etc.) This value is
    used to generate several elements of the book, such as the labels and the
    default titles. If the book is written in several languages, set the code
    of the main book language.
  * `publication_date`, sets the publication of the book in `YYYY-MM-DD` 
    format. If you set this value as null (`publication_date: ~`) **easybook**
    will automatically set the publication date to the day on which the book 
    is published.

The `contents` and `editions` options are explained in detail in the next
section and in the next chapter respectively. The `contents` option defines 
the book contents and their order. The `editions` option defines the features 
of each edition of the book.

## Content types ## {#content-types}

Books list their contents with the `contents` option in the `config.yml` file. 
After creating a new book with the `new` command, its default contents are:

~~~ .yaml
book:
    # ...
    contents:
        - { element: cover }
        - { element: toc   }
        - { element: chapter, number: 1, content: chapter1.md }
        - { element: chapter, number: 2, content: chapter2.md }
~~~

The most important option of each content is `element`, which defines its
content type. **easybook** currently supports 21 content types (the following
definitions have been [extracted from the Wikipedia][1]:

  * `acknowledgement`, often part of the preface, rather than a separate 
    section in its own right. It acknowledges those who contributed to the 
    creation of the book.
  * `afterword`, a piece of writing describing a time well after the time frame
    of the main story of the book.
  * `appendix`, is a supplemental addition to a given main work. It may correct
    errors, explain inconsistencies or otherwise detail or update the 
    information found in the main content of the book.
  * `author`, information about the book author/authors.
  * `chapter`, the most used type in regular books.
  * `conclusion`, the end of the book or document, where all of the pending
    issues are resolved or where idea and thoughts are settled.
  * `cover`, the cover of your book.
  * `dedication`, a page that usually precedes the text, in which the author
    names the person or people for whom he has written the book.
  * `edition`, information about the current edition of the book, including the
    publication date.
  * `epilogue`, a piece of writing at the end of a work of literature or drama,
    usually used to bring closure to the work.
  * `foreword`, usually written by any person other than the author of the  
    book. It often tells of some interaction between the writer of the 
    foreword and the story or the writer of the story.
  * `glossary`, consists of a set of definitions of words of importance to the
    work, normally alphabetized.
  * `introduction`, a beginning section which states the purpose and goals of
    the following writing.
  * `license`, information about the copyright holder or any other author or
    publisher rights that affect the book.
  * `lof` (*list of figures*), is an ordered list of the book images and
    illustrations, including their numbers and captions. The page in which the
    image is included is also shown for `pdf` type editions.
  * `lot` (*list of tables*), is an ordered list of the book data tables,
    including their numbers and captions. The page in which the table is 
    included is also shown for `pdf` type editions.
  * `part`, used to group several related chapters or appendices.
  * `preface`, generally covers the story of how the book came into being, or
    how the idea for the book was developed.
  * `prologue`, *written* by the narrator or any other character in the book.
    It's an opening to a story that establishes the setting and gives 
    background details, often some earlier story that ties into the main one, 
    and other miscellaneous information.
  * `title`, the page at or near the front which displays book title and 
    author, usually together with information relating to the publication of 
    the book.
  * `toc` (*table of contents*), a list of the parts of a book or document
    organized in the order in which the parts appear.

These 21 content types defined by **easybook** are enough to publish most 
books, but if you need it, you can also define your own
[new content types](#new-content-types).

Some book items don't require any other option besides `element`:

~~~ .yaml
book:
    # ...
    contents:
        - { element: cover }
        - { element: title }
        - { element: license }
        - { element: toc }
        - { element: chapter, number: 1, content: chapter1.md }
        - { element: chapter, number: 2, content: chapter2.md }
        - ...
~~~

In any case, each book item can define the following three options:

  * `number`, the number of the item used to generate the labels of each 
    section heading (`1.1`, `1.2`, `1.2.1`, `1.2.2`, etc.). It's mostly used 
    for chapters and appendices and **easybook** doesn't restrict its format, 
    so you can use Roman numerals (`I.1`, `I.2`), letters (`A.1`, `A.2`) or 
    any other symbol or string.
  * `content`, the name of the file with the contents of this item. The file
    name should include the extension (`.md` in the case of Markdown). The 
    value of this option is interpreted as the relative path from your book
    `Contents/` directory, so you can add all the subdirectories you want.
  * `title`, the title of the item. In the case of chapters and appendices, 
    it's not necessary to use it, because **easybook** extracts automatically
    their titles from the first `<h1>` of the chapter/appendix content. For the
    rest of content types, it's also unnecessary to set the title because the
    default title is usually enough (and it's displayed in the same language
    of the book contents). In sum, this option is only used for `part` content
    type, which separates the book contents into parts or sections.

## Defining book contents ##

The structure of a book can be very complex (cover, title page,
acknowledgements, dedication, chapters, parts, etc.). **easybook** supports
all the common book content types, but for now, we'll just focus on the
chapters. You can add as many chapters as you want and each one can be as
large or as short as you need. All you have to do is to list the book
chapters under the `contents` option of the `config.yml` file:

~~~ .yaml
book:
    # ...
    contents:
        - { element: cover }
        - { element: toc   }
        - { element: chapter, number: 1, content: chapter1.md }
        - { element: chapter, number: 2, content: chapter2.md }
~~~

Each line under the `contents` option defines a content of the book. Add your 
book chapters as `chapter` elements and set their number (with the `number` 
option) and the name of the files that hold their contents (with the `content` 
option).

Besides being lightning-fast, the main feature of **easybook** is its
flexibility, as it never forces you to work in a certain way. Do you want to
number your chapters in an *imaginative* way? People will think you're crazy,
but **easybook** allows you to do it:

~~~ .yaml
book:
    # ...
    contents:
        - { element: cover }
        - { element: toc   }
        - { element: chapter, number: 100, content: chapter1.md }
        - { element: chapter, number: 56,  content: chapter2.md }
~~~

Do you need letters instead of numbers? This is most appropriate for appendices instead of chapters, but **easybook** won't stop you from doing it:

~~~ .yaml
book:
    # ...
    contents:
        - { element: cover }
        - { element: toc   }
        - { element: chapter, number: A, content: chapter1.md }
        - { element: chapter, number: B, content: chapter2.md }
~~~

A recommended best practice is to use long and semantic names for book content 
files:

~~~ .yaml
book:
    # ...
    contents:
        - { element: cover }
        - { element: toc   }
        - { element: chapter, number: 1,
            content: 01-publishing-your-first-book.md }
        - { element: chapter, number: 2,
            content: 02-book-contents-and-configuration.md }
~~~

The most important thing about the `contents` option is the order in which
you list the book items. The published book will be always composed of those
contents and in that order. Therefore, the following configuration will output 
a book with the cover between the two chapters and the table of contents at 
the very end (it's completely crazy, but **easybook** allows you to do *almost*
anything):

~~~ .yaml
book:
    # ...
    contents:
        - { element: chapter, number: 1,
            content: 01-publishing-your-first-book.md }
        - { element: cover }
        - { element: chapter, number: 2,
            content: 02-book-contents-and-configuration.md }
        - { element: toc   }
~~~

By default, all content files are stored in the `Contents/` directory.
However, if your book is complex, you can divide the contents into several
subdirectories. Then, include the subdirectory in the `content` file path
(don't forget to enclose it with quotes if the file path has white spaces):

~~~ .yaml
book:
    # ...
    contents:
        - { element: cover }
        - { element: toc   }
        - { element: chapter,  number: 1,
            content: introduction/chapter1.md }
        - { element: chapter,  number: 2,
            content: introduction/chapter2.md }
        - { element: chapter,  number: 3,
            content: advanced/chapter1.md }
        - { element: appendix, number: A,
            content: appendices/appendixA.md }
~~~

The above configuration means that your book contents are distributed this
way:

~~~
<book_dir>/
    ...
    Contents/
        introduction/
            chapter1.md
            chapter2.md
        advanced/
            chapter1.md
        appendices/
            appendixA.md
~~~

### Different directories per book ###

Unless stated otherwise, the books are created in the `doc/` directory of the
**easybook** installation directory. If you want to save the contents in any 
other directory, add the `--dir` option when creating and/or publishig the 
book:

~~~ .cli
$ ./book new --dir="/Users/javier/books" "My First Book"
// the book is created in "/Users/javier/books/my-first-book"

$ ./book publish --dir="/Users/javier/books" my-first-book print
// the book is published in
// "/Users/javier/books/my-first-book/Output/print/book.pdf"
~~~

[1]: http://en.wikipedia.org/wiki/Book_design
