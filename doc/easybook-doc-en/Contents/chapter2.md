# Publishing your second book #

In the previous chapter, you learned how to create an publish a basic book with
**easybook**. However, we didn't mention any of the more advanced features.
This chapter explains all the available content types, how to master book
editions and how to tweak its appearance using templates.

## Content types ##

Books define their contents with the `contents` options in the `config.yml` file.
After creating a new book with the `new` command, the default contents are:

    [yaml]
    book:
        # ...
        contents:
            - { element: cover }
            - { element: toc   }
            - { element: chapter, number: 1, content: chapter1.md }
            - { element: chapter, number: 2, content: chapter2.md }

The most important option of each content is `element`, which defines its
content type. **easybook** currently supports 21 content types (the following
definitions have been [copied from Wikipedia](http://en.wikipedia.org/wiki/Book_design)):

  * `acknowledgement`, often part of the preface, rather than a separate section
  in its own right. It acknowledges those who contributed to the creation of the
  book.
  * `afterword`, a piece of writing describing a time well after the time frame
  of the main story of the book.
  * `appendix`, is a supplemental addition to a given main work. It may correct
  errors, explain inconsistencies or otherwise detail or update the information
  found in the main content of the book.
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
  * `foreword`, usually written by any person other than the author of the book.
  It often tells of some interaction between the writer of the foreword and the
  story or the writer of the story.
  * `glossary`, consists of a set of definitions of words of importance to the
  work, normally alphabetized.
  * `introduction`, a beginning section which states the purpose and goals of
  the following writing.
  * `license`, information about the copyright holder or any other author/publisher
  rights that affect the book.
  * `lof` (*list of figures*),  is an ordered list of the book images and
  illustrations, including their numbers and captions. The page in which the
  image is included is also shown for `pdf` type editions.
  * `lot` (*list of tables*),  is an ordered list of the book data tables,
  including their numbers and captions. The page in which the table is included
  is also shown for `pdf` type editions.
  * `part`, used to group several related chapters or appendices.
  * `preface`, generally covers the story of how the book came into being, or
  how the idea for the book was developed.
  * `prologue`, *written* by the narrator or any other character in the book.
  It's an opening to a story that establishes the setting and gives background
  details, often some earlier story that ties into the main one, and other
  miscellaneous information.
  * `title`, the page at or near the front which displays book title and author,
  usually together with information relating to the publication of the book.
  * `toc` (*table of contents*), a list of the parts of a book or document
  organized in the order in which the parts appear.

Most of the content types don't require any other option besides `element`:

    [yaml]
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
            - { element: author }
            - { element: acknowledgement }

The `appendix` and `chapter` content types can define the following options:

  * `number`, the number of the chapter or appendix. This value is used to
  generate the labels of each section heading (`1.1`, `1.2`, `1.2.1`, `1.2.2`,
  etc.) **easybook** doesn't restrict its format, so you can use Roman numerals
  (`I.1`, `I.2`), letters (`A.1`, `A.2`) or any other symbol or string.
  * `content`, the name of the file with the contents of this element. The file
  name should include the extension (`.md` in the case of Markdown). The value
  of this option is interpreted as the path from your book `Contents/` directory,
  so you can add all the subdirectories you want.

The `part` content type also define the following option:

  * `title`, the title of the part or section. In a printed book, a section is
  shown as a page that separates chapters. In a web book, the section is shown
  as a heading.

These 21 content types defined by **easybook** are enough to publish most books,
but if you need it, the next chapter explains how to create new content types.

## Editions ##

**easybook** is so flexible it allows you to publish the very same book in
radically different ways. This is possible thanks to the **editions**, that
define the specific characteristics with which the book is published.

Editions are defined under the `editions` options in `config.yml` file. By
default, the books created with the `new` command have four editions named
`ebook`, `print`, `web` and `website` with the following options:

    [yaml]
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

            print:
                format:         pdf
                include_styles: true
                isbn:           ~
                labels:         ['appendix', 'chapter']  # labels also available for: 'figure', 'table'
                margin:
                    top:        25mm
                    bottom:     25mm
                    inner:      30mm
                    outter:     20mm
                page_size:      A4
                toc:
                    deep:       2
                    elements:   ["appendix", "chapter"]
                two_sided:      true

            web:
                format:         html
                highlight_code: true
                include_styles: true
                labels:         ['appendix', 'chapter']  # labels also available for: 'figure', 'table'
                toc:
                    deep:       2
                    elements:   ["appendix", "chapter"]

            website:
                extends:        web
                format:         html_chunked

The name of each edition must be unique for the same book and cannot contain
spaces.  The edition name is used as the subdirectory inside `Output/` directory
to separate the contents of each edition. You can define as many editions as you
need, but all must belong to one of the following four types defined by the
`format` option:

  * `epub`, the book is published as an e-book named `book.epub`
  * `pdf`, the book is published as a PDF file named `book.pdf`
  * `html`, the book is published as a HTML page named `book.html`
  * `html_chunked`, the book is published as a static website in a directory
  named `book`

Editions can modify the aspect of the published book through several
configuration options. The `epub`, `html` and `html_chunked` edition types share
the same options:

  * `highligh_code`, if `true` the syntax of the code listings is highlighted
  (this option is a placeholder and it doesn't work for the momment).
  * `include_styles`, if `true` **easybook** default CSS styles are applied.
  * `labels`, indicates the content types for which **easybook** will add labels
  to their section headings. By default labels are only added to headings of
  chapters and appendices. In addition to **easybook** content types, you can
  use two special values called `figure` and `table` to add labels for book
  images and tables. If you want to show no labels in your book, delete all
  values of this option: `labels: []`.
  * `toc`, sets the options of the table of contents. It's ignored unless the
  book has at least one `toc` element type. It has two options:
    * `deep`, the maximum heading level included in the TOC (`1` is the lowest
    possible number and would only show `<h1>` level headings; `6` is the
    highest possible value and would show all `<h1>`, `<h2>`, `<h3>`, `<h4>`,
    `<h5>` and `<h6>` headings).
    * `elements`, the type of elements included in the TOC (by default, only
    `appendix`, `chapter` and `part` are included).

The `pdf` editions can define even more options:

  * `isbn`, the ISBN-10 or ISBN-13 code of the book (this option is a placeholder
  and it doesn't work for the momment).
  * `include_styles`, if `true` **easybook** will define the layout, typesetting
  and design of the book. Use this option to create stunning books effortlessly.
  The next chapter will explain how to define your own styles.
  * `labels`, it's the same option and has the same meaning as for the `epub`,
  `html` and `html_chunked` editions.
  * `margin`, sets the four margins of the printed book: `top`, `bottom`,
  `inner` and `outter`. If the book is one-sided, `inner` equals left margin and
  `outter` equals right margin. The values of the margins can be set with any
  CSS valid lenght unit (`1in`, `25mm`, `2.5cm`).
  * `page_size`, the page size of the printed book. **easybook** supports
  [tens of page sizes](http://www.princexml.com/doc/7.1/page-size/) thanks to
  PrinceXML: `US-Letter`, `US-Legal`, `crown-quarto`, `A4`, `A3`, etc.
  * `toc`, it's the same option and has the same meaning as for the `epub`,
  `html` and `html_chunked` editions
  * `two_sided`, if `true` the PDF file is ready for two-sided printing.

In addition to all these options, editions can set a very useful option named
`extends`. The value of this option indicates the name of the edition from which
this edition *inherits*. When an edition *inherits* from another edition, the
options of the parent edition are copied on the *heir* edition, which can then
override any value.

Imagine for example you want to publish one PDF book with three slightly
different designs. The draft version (`draft`) must be double-sided and must
have very small margins to reduce its length, the normal version (`print`) is
one-sided and has normal margins. The version prepared for lulu.com website
(`lulu`) is similar to the normal version, except is double-sided:

    [yaml]
    book:
        # ...
        editions:
            print:
                format:       pdf
                isbn:         ~
                labels:       ['appendix', 'chapter']  # labels also available for: 'figure', 'table'
                margin:
                    top:      25mm
                    bottom:   25mm
                    inner:    30mm
                    outter:   20mm
                page_size:    A4
                toc:
                    deep:     2
                    elements: ['appendix', 'chapter']
                two_sided:    false

            draft:
                extends:      print
                margin:
                    top:      15mm
                    bottom:   15mm
                    inner:    20mm
                    outter:   10mm
                two_sided:    true

            lulu:
                extends:      print
                two_sided:    true

The only limitation of `extends` is that it only works with one level inheritance.
Therefore, and edition cannot extend another edition that extends a third one.

## Themes ##

A theme is a set of templates, stylesheets and other resources that define the
visual design of the books. **easybook** already includes a theme for each
edition type (`epub`, `pdf`, `html`, `html_chunked`), so your books will look
professional without any effort.

Bundled themes are located in the `app/Resources/Themes/` directory. If you need
to change the design of your books, **don't** modify the files in these
directories. The next chapter explains how to easily override any template or
resource for your book.

### Default contents ###

In most books, the only elements that define their own content are chapters and
appendices (with the `content` option). In addition, **easybook** defines
sensible default contents for some content types. If your book for example
includes an inner cover (`title` content type) without any contents file:

    [yaml]
    book:
        # ...
        contents:
            - ...
            - { element: title }
            - ...

**easybook** will use the following as the content for this element:

    [twig]
    <h1>{{ book.title }}</h1>
    <h2>{{ book.author }}</h2>
    <h3>{{ book.edition }}</h3>

The default title page shows the title, the name of the author and the current
edition of the book. All these values are configured in the `book` option of
`config.yml` file.

The contents defined by **easybook** depend on both the edition being published
and the content type. You can access these default contents on the `Contents/`
directory of the theme.

### Custom contents ###

If you don't want to use **easybook** default contents for some element, simply
add the `content` option indicating the file that defines its contents:

    [yaml]
    book:
        # ...
        contents:
            - ...
            - { element: license, content: creative-commons.md }
            - { element: title, content: my-own-title-page.md }
            - ...

### Default templates ###

The content of each element is *decorated* with a template before including it
in the published book. **easybook** templates are created with
[Twig](http://twig.sensiolabs.org/), the best templating language for PHP. You
can access all the default templates in the `Templates/` directory of the theme.

See for example the template used to decorate each chapter of a PDF book:

    [twig]
    <div class="page:chapter new-page">

    <h1 id="{{ item.slug }}"><span>{{ item.label }}</span> {{ item.title }}</h1>

    {{ item.content }}

    </div>

The data of the content being decorated are accessible through a special
variable called `item`, which holds the following properties:

  * `item.title`, is the title of the content. It's obtained from the `title`
  configuration option, from the content or from the default titles defined by
  **easybook**.
  * `item.slug`, is a *safe* version of the `title` that doesn't include white
  spaces or any other *troublesome characters* (such as accents, `.`, `?`, `!`,
  ...). This value is used on URLs, on `id` HTML attributes, etc.
  * `item.label`, the label of the main heading of the content. Usually it's
  only defined for chapters (`Chapter XX`), parts (`Part XX`) and appendices
  (`Apendix XX`).
  * `toc`, array with the table of contents of this element. It's empty for most
  of the content types  (`cover`, `license`, etc.) but can be very large for
  complex chapters and appendices.
  * `item.content`, the content of the element prepared to be included in the
  book (it already has been converted from its original Markdown format).
  * `item.original`, the original content of the element without any modification.
  * `item.config`, array with all the configuration options defined for the
  element in the `config.yml` file. Internally it holds `number`, `title`,
  `content` and `element` properties. For example, you can access the type of
  the element with the following expression: `{{ item.config.element }}`.

Images and tables are decorated with special templates called `figure.twig` and
`table.twig` which have access to the following variables:

   * `item.caption`, is the title of the image/table as indicated in the
   original content.
   * `item.content`, is the complete HTML code generated to display the image/table
   in the book (`<img src="..." alt="..." />` or `<table> ... </ table>`).
   * `item.label`, is the label of the image/table. This value is empty unless
   the `labels` option of the edition includes `figure` and/or `table`.
   * `item.number`, is the autogenerated number of the image/table inside the
   item being processed. The first image/table is considered to be the number
   `1` and the following are increased by one.
   * `element.number`, is the number of the parent element (chapter, appendix,
   etc.) of the image/table. This property will be for example `5` for all the
   images/tables included in the chapter number `5` of the book.

In addition to these properties, all the **easybook** templates have access to
three global variables:
  
  * `book`, provides direct access to the configuration options defined under
  `book` in `config.yml` file. You can access for example the book author in any
  template using `{{ book.author }}` and the current **easybook** version using
  `{{ book.generator.version }}`.
  * `edition`, provides direct access to the configuration options of the edition 
  curently being published.
  * `app`, provides direct access to all configuration options and services of
  **easybook** (defined in the `src/DependencyInjection/Application.php` file).

### Custom templates ###

In the next chapter you'll learn how to use your own templates instead of
**easybook** default templates.

### Default styles ###

The appearance of the books is defined by CSS stylesheets, even in the case of
`epub` and `pdf` type editions. **easybook** includes several default styles in
the `Templates/` directory of the theme. CSS styling in `epub` editions is pretty
limited due to the absymal support of most e-book readers.

For `html` and `html_chunked` editions, the limit is the highest CSS version that
you can use on your website (CSS 2.1, CSS 3, CSS 4, etc..) because every modern
web browser offers an excelent CSS support.

In the case of `pdf` editions, your imagination is the only limit. PDF books are
generated by [PrinceXML](http://www.princexml.com/) transforming a HTML document
into a PDF file using a CSS stylesheet. PrinceXML defines tens of new CSS
properties and options unavailable in the CSS standards. Using these exclusive
properties you can easily add amazing features to your book design and layout.
We strongly recommend you take a look at the `styles.css.twig` file of the `Pdf/`
theme to learn some of the most advanced features. It will blow your mind away!
And don't forget to read [PrinceXML documentation](http://www.princexml.com/doc/8.0/).

### Custom styles ###

In the next chapter you'll learn how to use your own CSS styles instead of
**easybook** default styles.

### Default fonts ###

**easybook** bundles several free and high-quality fonts to make published books
look professional. You can see the fonts and their license in the
`app/Resources/Fonts/` directory.

### Default labels and titles ###

**easybook** requires each content to have its own title.  Chapters and appendices
include the title within their own content as the first section heading and parts
define it in the `title` option of the `config.yml` file. For the rest of content
types, **easybook** assigns a default title that varies depending on the language
in which the book is written. You can see the titles applied to English books in
`app/Resources/Translations/titles.en.yml`.

Moreover, the book can add labels (`Chapter XX`, `Appendix XX`, etc.) to section
titles using the `labels` option. As explained before, this option indicates the
content types for which tags are added automatically. By default the book only
adds tags in chapters and appendices. The default labels applied to books written
in English are defined in `app/Resources/Translations/labels.en.yml` file.

Unlike the titles, labels can contain variable parts, such as the appendix or
chapter number. Therefore, **easybook** uses Twig templates to define each label:

    [yaml]
    label:
        figure: 'Figure {{ element.number }}.{{ item.number }}'
        part:   'Part {{ item.number }}'
        table:  'Table {{ element.number }}.{{ item.number }}'

The templates for `figure` and `table` labels can use the same variables as
`figure.twig` and `table.twig` templates explained before. Therefore,
`{{ item.number }}` shows the autogenerated number of the image/table and
`{{ element.number }}` shows the number of the chapter or appendix.

Chapters and appendices must define six different labels, each corresponding to
one of the six heading levels (`<h1>`, `<h2>`, `<h3>`, `<h4>`, `<h5>` and `<h6>`).
In the following example, appendices only include a label in their titles, thus
leaving empty the last five levels:

    [yaml]
    label:
        appendix:
            - 'Appendix {{ item.number }}'
            - ''
            - ''
            - ''
            - ''
            - ''

Labels can access to all configuration options defined by the item in the
`config.yml` file. The label of a chapter can then use `{{ item.number }}` to
show the number of the chapter. In addition to these properties, label templates
have access to two special variables called `level` and `counters`.

The `level` variable indicates the section heading level for which you want to
get the label, being `1` the level of `<h1>` heading and `6` the level of `<h6>`
headings. The `counters` variable is an array with the counters of all heading
levels. For that reason, to show second-level headings as `1.1`, `1.2` ... `7.1`,
`7.2` you can use the following expression:

    [yaml]
    label:
        chapter:
            - 'Chapter {{ item.number }}'
            - '{{ item.counters[0] }}.{{ item.counters [1] }}'
            - ...

Extending the previous example, you can use the following templates to format
all the heading levels as `1.1`, `1.1.1`, `1.1.1.1`, etc.:

    [yaml]
     label:
         chapter:
             - 'Chapter {{ item.number }} '
             - '{{ item.counters[0:2]|join(".") }}'  # 1.1
             - '{{ item.counters[0:3]|join(".") }}'  # 1.1.1
             - '{{ item.counters[0:4]|join(".") }}'  # 1.1.1.1
             - '{{ item.counters[0:5]|join(".") }}'  # 1.1.1.1.1
             - '{{ item.counters[0:6]|join(".") }}'  # 1.1.1.1.1.1

This last example clearly shows what you can achieve by combining the
flexibility of **easybook** and the power of Twig.

### Custom labels and titles ###

If you want to use your own titles and labels, you must first create the
`Translations` directory inside the `Resources` directory of your book (you must
also create the latter if it doesn't exist). Then add the new label file in one
of the following directories:

   1. `<book>/Resources/Translations/<edition-name>/labels.en.yml`, if you want
   to change the labels in a single edition. The subdirectory of `Translations`
   must be named exactly like the edition being published.
   2. `<book>/Resources/Translations/<edition-type>/labels.en.yml`, if you want
   to change the labels in all editions of the same type. The directory in
   `Translations` can only be named `epub`, `html`, `html_chunked` or `pdf`.
   3. `<book>/Resources/Translations/labels.en.yml`, if you want to change the
   labels on all the editions of the book, regardless of its name or type.

When you use your own label file, you don't have to define the value of all
labels. Add only the labels that you want to modify and **easybook** will assign
to the rest their default values. Therefore, to only modify the label of the
images in any book edition, create a new `<libro>/Resources/Translations/labels.en.yml`
file and add just the following content:

    [yaml]
    label:
        figure: 'Illustration {{ item.number }}'

If you want to change the titles instead of the labels, follow the same steps
but create a file called `titles.en.yml` instead of `labels.en.yml`. If your book
isn't written in English, replace `en` for the code of the other language (e.g.
`labels.es.yml` for Spanish labels).
