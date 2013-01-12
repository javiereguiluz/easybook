# Publishing your second book #

In the previous chapter, you learned how to create a basic book and publish it with **easybook**. However, we didn't mention any of the more advanced features. This chapter explains all the available content types, how to master book editions and how to tweak its appearance using templates.

## Content types ##

Books define their contents with the `contents` options in the `config.yml` file. After creating a new book with the `new` command, the default contents are:

    book:
        ...
        contents:
            - { element: cover }
            - { element: toc   }
            - { element: chapter, number: 1, content: chapter1.md }
            - { element: chapter, number: 2, content: chapter2.md }

The most important option of each content is `element`, which defines its content type. **easybook** currently defines the following elevent content types:

  * `acknowledgement`, where the author acknowledges those who contributed to the creation of the book.
  * `appendix`, similar to chapters, but it usually contains related but supplemental additions to the book main work.
  * `author`, information about the book author/authors.
  * `chapter`, the most used type in regular books.
  * `cover`, the cover of your book.
  * `dedication`, a page that usually precedes the text, in which the author names the person or people for whom he has written the book.
  * `edition`, information about the current edition of the book, including the publication date.
  * `license`, information about the copyright holder or any other author/publisher rights that affect the book.
  * `part`, used to group several related chapters or appendices.
  * `title`, the page that displays book title and author, usually together with information relating to the publication of the book.
  * `toc`, the table of contents.

Most of the content types don't require any other option besides `element`:

    book:
        ...
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

  * `number`, the number of the chapter or appendix. This value is used to generate the labels of each section heading (`1.1`, `1.2`, `1.2.1`, `1.2.2`, etc.) **easybook** doesn't restrict its format, so you can use Roman numerals (`I.1`, `I.2`), letters (`A.1`, `A.2`) or any other symbol or string.
  * `content`, the name of the file with the contents of this element. The file name should include the extension (`.md` in the case of Markdown). The value of this option is interpreted as the path from your book `Contents/` directory, so you can add all the subdirectories you want.

The `part` content type also define the following option:

  * `title`, the title of the part or section. In a printed book, a section is shown as a page that separates chapters. In a web book, the section is only shown in the table of contents.

These elevent content types defined by **easybook** are enough to publish most books, but if you need it, the next chapter explains how to create new content types.

## Editions ##

**easybook** is so flexible it allows you to publish the very same book in radically different ways. This is possible thanks to the **editions**, that define the specific characteristics with which the book is published.

Editions are defined under the `editions` options in `config.yml` file. By default, the books created with the `new` command have three editions named `print`, `web` and `website` with the following options:

    book:
        ...
        editions:
            print:
                format:         pdf
                auto_label:     true
                include_styles: true
                isbn:           ~
                margin:
                    top:        25mm
                    bottom:     25mm
                    inner:      30mm
                    outter:     20mm
                page_size:      A4
                two_sided:      true
                toc:
                    deep:       2
                    elements:   ["appendix", "chapter"]
    
            web:
                format:         html
                auto_label:     true
                highlight_code: true
                include_styles: true
                toc:
                    deep:       2
                    elements:   ["appendix", "chapter"]

            website:
                extends:        web
                format:         html_chunked

The name of each edition must be unique for the same book and cannot contain spaces.  The edition name is used as the subdirectory inside `Output/` directory to separate the contents of each edition. You can define as many editions as you need, but all must belong to one of the following three types defined by the `format` option:

  * `pdf`, the book is published as a PDF file named `book.pdf`
  * `html`, the book is published as a HTML page named `book.html`.
  * `html_chunked`, the book is published as a static website in a directory named `book`.

Editions can modify the aspect of the published book through several configuration options. The `html` and `html_chunked`  edition types share the same options:

  * `auto_label`, if `true` all the book headings are prefixed with labels (`1.1`, `1.2`, `1.2.1`, `1.2.2`, etc.)
  * `highligh_code`, if `true` the syntax of the code listings is highlighted (this option is a placeholder and it doesn't work for the momment).
  * `include_styles`, if `true` the generated HTML pages include a link to **easybook** default CSS file.
  * `toc`, sets the options of the table of contents. It's ignored unless the book has at least one `toc` element type. It has two options:
    * `deep`, the maximum heading level included in the TOC (`1` is the lowest possible number and would only show `<h1>` level headings; `6` is the highest possible value and would show all `<h1>`, `<h2>`, `<h3>`, `<h4>`, `<h5>` and `<h6>` headings).
    * `items`, the type of elements included in the TOC (by default, only `appendix`, `chapter` and `part` are included).

The `pdf` editions can define even more options:

  * `auto_label`, it's the same option and has the same meaning as for the `html` and `html_chunked` editions
  * `isbn`, the ISBN-10 or ISBN-13 code of the book (this option is a placeholder and it doesn't work for the momment).
  * `include_styles`, if `true` **easybook** will decide the layout, typesetting and design of the book. Use this option to create stunning books effortlessly. The next chapter will explain how to define your own styles.
  * `margin`, sets the four margins of the printed book: `top`, `bottom`, `inner` and `outter`. If the book is one-sided, `inner` equals left margin and `outter` equals right margin. The values of the margins can be set with any CSS valid lenght unit (`1in`, `25mm`, `2.5cm`).
  * `page_size`, the page size of the printed book. **easybook** only supports `A4` size for the moment (`A4` size is 8.27 inches × 11.69 inches).
  * `toc`, it's the same option and has the same meaning as for the `html` and `html_chunked` editions
  * `two_sided`, if `true` the PDF file is ready for two-sided printing.

In addition to all these options, editions can set a very useful option named `extends`. The value of this option indicates the name of the edition from which this edition *inherits*. When an edition *inherits* from another edition, the options of the parent edition are copied on the *heir* edition, which can then override any value.

Imagine for example you want to publish one PDF book with three slightly different design. The draft version (`draft`) must be double-sided and must have very small margins to reduce its length, the normal version (`print`) is one-sided and has normal margins. The version prepared for lulu.com website (`lulu`) is similar to the normal version, except is double-sided:

    book:
        ...
        editions:
            print:
                format:       pdf
                isbn:         ~
                auto_label:   true
                two_sided:    false
                page_size:    A4
                margin:
                    top:      25mm
                    bottom:   25mm
                    inner:    30mm
                    outter:   20mm
                toc:
                    deep:     2
                    elements: ['appendix', 'chapter']
    
            draft:
                extends:      print
                two_sided:    true
                margin:
                    top:      15mm
                    bottom:   15mm
                    inner:    20mm
                    outter:   10mm

            lulu:
                extends:      print
                two_sided:    true

The only limitation of `extends` is that it only works with one level inheritance. Therefore, and edition cannot extend another edition that extends a third one.

## Themes ##

A theme is a set of templates, stylesheets and other resources that define the visual design of the books. **easybook** already includes a theme for each edition type (`pdf`, `html`, `html_chunked`), so your books will look professional without any effort.

Bundled themes are located in the `app/Resources/Themes/` directory. If you need to change the design of your books, **don't** modify the files in these directories. The next chapter explains how to easily override any template or resource for your book.

### Default contents ###

In most books, the only elements that define their own content are the chapters and appendices (with the `content` options). **easybook** defines sensible default contents for every content type. If the book adds for example an inner cover (`title` content type):

    book:
        ...
        contents:
            - ...
            - { element: title }
            - ...

**easybook** will use the following as the default content of this element:

    <h1>{{ book.title }}</h1>
    <h2>{{ book.author }}</h2>
    <h3>{{ book.edition }}</h3>

The default title page shows the title, the name of the author and the current edition of the book. All these values are configured in the `book` option of `config.yml` file.

The contents defined by **easybook** depend on both the edition being published and the content type. You can access these default contents on the `Contents/` directory of the theme.

### Custom contents ###

If you don't want to use **easybook** default contents for these elements, simply add the `content` option indicating the file that defines its contents:

    book:
        ...
        contents:
            - ...
            - { element: title, content: my-own-title-page.md }
            - { element: toc, content: my-table-of-contents.md }
            - ...

### Default templates ###

The content of each element is *decorated* with a template before including it in the published book. **easybook** templates are created with [Twig](http://twig.sensiolabs.org/), the best templating language for PHP. You can access all the default templates in the `Templates/` directory of the theme.

See for example the template used to decorate each chapter of a PDF book:

    <div class="page:chapter new-page">

    <h1 id="{{ item.slug }}"><span>{{ item.label }}</span> {{ item.title }}</h1>

    {{ item.content }}

    </div>

The data of the content being decorated are accessible through a special variable called `item`, which holds the following properties:

  * `item.title`, is the title of the content. It's obtained from the `title` configuration option, from the content or from the default titles defined by **easybook**.
  * `item.slug`, is a *safe* version of the `title` that doesn't include white spaces or any other *troublesome characters* (such as accents, `.`, `?`, `!`, ...). This value is used on URLs, on `id` HTML attributes, etc.
  * `item.label`, the label of the main heading of the content. Usually it's only defined for chapters (`Chapter XX`), parts (`Part XX`) and appendices (`Apendix XX`).
  * `toc`, array with the table of contents of this element. It's empty for most of the content types  (`cover`, `license`, etc.) but can be very large for complex chapters and appendices.
  * `item.content`, the content of the element prepared to be included in the book (it already has been converted rom its original Markdown format).
  * `item.original`, the original content of the element without any modification.
  * `item.config`, array with all the configuration options defined for the elemento in the `config.yml` file. Internally it holds `number`, `title`, `content` and `element` properties. For example, you can access the type of the element with the following expression: `{{ item.config.element }}`.

In addition to these item properties, all the **easybook** templates have access to three global variables:
  
  * `book`, provides direct access to the configuration options defined under `book` in `config.yml` file. You can access for example the book author in any template using `{{ book.author }}` and the current **easybook** version using `{{ book.generator.version }}`.
  * `edition`, provides direct access to the configuration options of the edition curently being published.
  * `app`, provides direct access to all configuration options and services of **easybook** (defined in the `src/DependencyInjection/Application.php` file).

### Custom templates ###

In the next chapter you'll learn how to use your own templates instead of **easybook** default templates.

### Default styles ###

The appearance of the books is defined by CSS stylesheets, even in the case of the `pdf` type editions. **easybook** includes several default styles in the `Templates/` directory of the theme. For `html` and `html_chunked` editions, the limit is the highest CSS version that you can use on your website (CSS 2.1, CSS 3, CSS 4, etc..)

In the case of `pdf` editions, your imagination is the only limit. PDF books are generated by [PrinceXML](http://www.princexml.com/) transforming a HTML document using a CSS stylesheet. PrinceXML defines tens of new CSS properties and options unavailable in the CSS standards. Using these exclusive properties you can easily add amazing features to your book design and layout. We strongly recommend you take a look at the `styles.css.twig` file of the `Pdf/` theme to learn some of the most advanced features. It will blow your mind away! And don't forget to read [PrinceXML documentation](http://www.princexml.com/doc/8.0/).

### Custom styles ###

In the next chapter you'll learn how to use your own CSS styles instead of **easybook** default styles.

### Default fonts ###

**easybook** bundles several free and high-quality fonts to make published books look professional. You can see the fonts and their license in the `app/Resources/Fonts/` directory.

### Default labels and titles ###

**easybook** requires each content to have its own title.  Chapters and appendices include the title within their own content and parts define it in the `title` option of the `config.yml` file. For the rest of content types, **easybook** assigns a default title that varies depending on the language in which the book is written. You can see the titles applied to English books in `app/Resources/Translations/titles.en.yml`.

Similarly, if the book sets the `auto_labels` option, **easybook** adds labels to the headings of `chapter`, `appendix` and `part` content types. You can see the default labels applied to books written in English in `app/Resources/Translations/labels.en.yml` file.

Unlike the titles, labels can contain variable parts, such as the appendix or chapter number. Therefore, **easybook** uses Twig templates to define each label:

    label:
        figure: 'Figure {{ item.number }}.{{ counter }} '
        part:   'Part {{ item.number }} '
        table:  'Table {{ item.number }}.{{ counter }} '

The `item.number` property is the number (or letter) defined in the `number` option of the element in `config.yml` file. Chapters and appendices must define six different labels, each corresponding to one of the six headings levels (`<h1>`, `<h2>`, `<h3>`, `<h4>`, `<h5>` and `<h6>`). In the following example, appendices only include a label in its title, thus leaving empty the last five levels:

    label:
        appendix: ['Appendix {{ item.number }} ', '', '', '', '', '']

In addition to `item.number` property, label templates have access to a special variable called `counters` which is an array with the counters of all heading levels. For that reason, to show second-level headings as `1.1`, `1.2` ... `7.1`, `7.2` you can use the following expression:

    label:
        chapter:
            - 'Chapter {{ item.number }} '
            - '{{ counters[0] }}.{{ counters[1] }}'
            - ...

Extending the previous example, you can use the following templates to format all the heading levels as `1.1`, `1.1.1`, `1.1.1.1`, etc.:
 
     label:
         chapter:
             - 'Chapter {{ item.number }} '
             - '{{ counters[0:2]|join(".") }}'  # 1.1
             - '{{ counters[0:3]|join(".") }}'  # 1.1.1
             - '{{ counters[0:4]|join(".") }}'  # 1.1.1.1
             - '{{ counters[0:5]|join(".") }}'  # 1.1.1.1.1
             - '{{ counters[0:6]|join(".") }}'  # 1.1.1.1.1.1

This last example clearly shows what you can achieve by combining the flexibility of **easybook** and the power of Twig.

### Custom labels and titles ###

If you want to use your own titles and labels, you must first create the `Translations` directory inside the `Resources` directory of your book (you must also create the latter if it doesn't exist). Then add the new label file in one of the following directories:

   1. `<book>/Resources/Translations/<edition-name>/labels.en.yml`, if you want to change the labels in a single edition. The subdirectory of `Translations` must be named exactly like the edition being published.
   2. `<book>/Resources/Translations/<edition-type>/labels.en.yml`, if you want to change the labels in all editions of the same type. The directory in `Translations` can only be called `html`, `html_chunked` or `pdf`.
   3. `<book>/Resources/Translations/labels.en.yml`, if you want to change the labels on all the editions of the book, regardless of its name or type.

When you use your own label file, you don't have to define the value of all labels. Add only the labels that you want to modify and **easybook** will assign to the rest their default values.

If you want to change the titles instead of the labels, follow the same steps but create a file called `titles.en.yml` instead of `labels.en.yml`. If your book isn't written in English, replace `en` for the code of the other language (e.g. `labels.es.yml` for Spanish labels).
