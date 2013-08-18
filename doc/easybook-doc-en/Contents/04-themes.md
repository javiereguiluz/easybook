# Themes #

A theme is a set of templates, stylesheets and other resources that define the
visual design of the book. **easybook** already includes a theme for each
edition type (`epub`, `mobi`, `pdf`, `html`, `html_chunked`), so your books 
will look professional without any effort.

**easybook** themes are located in the `app/Resources/Themes/` directory. If 
you need to change the design of your books, **don't** modify those files, but
use the overriding techniques explained in this chapter.

## Contents ##

### Default contents ###

In most books, the only elements that define their own content are chapters and
appendices (with the `content` option). For that reason, **easybook** defines
sensible default contents for some content types. If your book for example
includes a `license` content type without any content:

~~~ .yaml
book:
    # ...
    contents:
        - ...
        - { element: license }
        - { element: chapter, content: 'chapter1.md' }
        - ...
~~~

**easybook** will use the following as the content for this element:

~~~ .twig
&copy; Copyright {{ book.publication_date|default('now')|date('Y') }},
{{ book.author }}

**ALL RIGHTS RESERVED**. This book contains material protected under 
International and Federal Copyright Laws and Treaties. Any unauthorized 
reprint or use of this material is prohibited. No part of this book may 
be reproduced or transmitted in any form or by any means, electronic or 
mechanical, including photocopying, recording, or by any information
storage  and retrieval system without express written permission from the 
author and/or publisher.
~~~

The default `license` page displays a copyright notice corresponding to either
the book publication date (if defined) or the current year. Similarly, the
`title` content type defines the following default content:

~~~ .twig
<h1>{{ book.title }}</h1>
<h2>{{ book.author }}</h2>
<h3>{{ book.edition }}</h3>
~~~

The contents defined by **easybook** depend on both the edition being published
and the content type. You can access these default contents at the `Contents/`
directory of the theme.

### Custom contents ###

If you don't want to use **easybook** default contents for some element, simply
add the `content` option indicating the file that defines its contents:

~~~ .yaml
book:
    # ...
    contents:
        - ...
        - { element: license, content: creative-commons.md }
        - { element: title,   content: my-own-title-page.md }
        - ...
~~~

## Templates ##

### Default templates ### {#default-templates}

The content of each element is *decorated* with a template before including it
in the published book. **easybook** templates are created with [Twig][1], the 
best templating language for PHP. You can access all the default templates in 
the `Templates/` directory of the theme.

This is for example the template used to decorate each chapter of a PDF book:

~~~ .twig
<div class="item chapter">
    <h1 id="{{ item.slug }}">
        <span>{{ item.label }}</span> {{ item.title }}
    </h1>

    {{ item.content }}
</div>
~~~

The data of the book item being decorated is accessible through a special
variable called `item`, which stores the following properties:

  * `item.title`, is the title of the book item. By order of priority, this 
    value is obtained from 1) the `title` configuration option of the item,
    2) the `<h1>` title of the item content, 3) the default title defined by
    **easybook** for this kind of element.
  * `item.slug`, is a *safe* version of the `title` that doesn't include white
    spaces or any other *troublesome characters* (such as accents, `.`, `?`,
    `!`, ...). This value is used on URLs, on `id` HTML attributes, etc.
  * `item.label`, the label of the main heading of the item. Usually it's
    only defined for chapters (`Chapter XX`), parts (`Part XX`) and appendices
    (`Apendix XX`).
  * `toc`, array with the table of contents of this item. It's empty for 
    most of the content types (`cover`, `license`, etc.) but can be very 
    large for complex chapters and appendices.
  * `item.content`, the content of the item prepared to be included in the
    book (it has already been converted from its original Markdown format).
  * `item.original`, the original content of the item without any modification.
  * `item.config`, array with all the configuration options defined for the
    item in the `config.yml` file. Internally it stores the `number`, `title`,
    `content` and `element` properties. For example, you can access the type of
    the item with the following expression: `{{ item.config.element }}`.

Images and tables are decorated with special templates called `figure.twig` and
`table.twig` which have access to the following variables:

   * `item.caption`, is the title of the image/table as indicated in the
     original content.
   * `item.content`, is the complete HTML code generated to display the image/
     table in the book (`<img src="..." alt="..." />` or
     `<table> ... </ table>`).
   * `item.label`, is the label of the image/table. This value is empty unless
     the `labels` option of the edition includes `figure` and/or `table`.
   * `item.number`, is the autogenerated number of the image/table inside the
     item being processed. The first image/table is considered to be the number
     `1` and the following are increased by one.
   * `element.number`, is the number of the parent element (chapter, appendix,
     etc.) of the image/table. This property will be for example `5` for all 
     the images/tables included in the chapter number `5` of the book.

In addition to these properties, all the **easybook** templates have access to
three global variables:
  
  * `book`, provides direct access to the configuration options defined under
    `book` in `config.yml` file. You can access for example the book author in 
    any template using `{{ book.author }}` and the current **easybook** 
    version using `{{ book.generator.version }}`.
  * `edition`, provides direct access to the configuration options of the 
    edition currently being published.
  * `app`, provides direct access to all the configuration options and 
    services of **easybook** (defined in the
    `src/DependencyInjection/Application.php` file).

### Custom templates ### {#custom-templates}

If you want to fine-tune the appearance of your book, you can start by using
your own templates instead the default ones. First, create a directory called
`Resources/` within the directory of your book and inside it, create another 
directory called `Templates/`.

You can now define your own templates in the `Resources/Templates/` to apply
them to the published book. The name of the template must match the item type
(`chapter`, `dedication`, `author`, etc..) and its extension should be
`.twig` because they are always created with the Twig templating language. 
Consequently, if you want to modify the design of the chapters, you must 
create a template called `chapter.twig` in the `<book>/Resources/Templates/`
directory.

When a book is published, each book item is decorated with its own template.
**easybook** looks for each template in the following directories (showed in
order of priority). If none of these templates are found, it will use the 
default template:

  1. `<book>/Resources/Templates/<edition-name>/template.twig`, allows you to
     modify the design for each edition. The directory inside `Templates/` must
     match exactly the name of the edition being published.
  2. `<book>/Resources/Templates/<edition-type>/template.twig`, allows you to
     modify the design for all the editions of the same format. The directory 
     inside `Templates/` must be named `epub`, `mobi`, `html`, `html_chunked` 
     or `pdf`.
  3. `<book>/Resources/Templates/template.twig`, applies the same same design
     to all editions. The template is located in the `Resources/Templates` 
     directory without including it in any subdirectory. This option is rarely 
     used because it usually doesn't make sense to apply the same style 
     regardless of whether content is converted into a PDF file or a website.

As explained in the [previous section](#default-templates), the templates have 
access to a variable named `item` with all the information about the item that 
is being decorated and three global variables with information about the book
(`book`), the edition being published (`edition`) and the entire application
(`app`).

In addition to the templates associated with the book item (`chapter.twig`, 
etc.) each edition format defines several special templates, as explained in
the following chapters.

## Styles ##

**easybook** uses CSS stylesheets to define the visual design of the books, 
even for the `epub` and `pdf` type editions.

### Default styles ###

**easybook** applies by default some styling to the book that depends on the
selected `theme` and the edition being published. If you want to disable these
styles for a specific edition, set its `include_styles` option to `false` and
no default style will be applied to the book.

The CSS styles for each format are defined in the `styles.css.twig` file
located at the `<theme>/<edition-format>/Templates/` directory of the theme.
The CSS styles applied to the book vary greatly depending on the format of the
published edition:

  * `epub` and `mobi`: CSS styling is pretty limited due to the abysmal 
    support of most e-book readers.
  * `html` and `html_chunked`: the only limit is the highest CSS version 
    that you can use on your website (CSS 2.1, CSS 3, CSS 4, etc..) because 
    every modern web browser offers an excellent CSS support.
  * `pdf`: your imagination is the limit. PDF books are generated by
    [PrinceXML][2], which defines tens of new CSS properties and options 
    unavailable in the CSS standards.

### Custom styles ###

In addition to the **easybook** default styles, your book can define its own
CSS styles to tweak its design. Unless you need a complete redesign of the 
book, it's recommended to maintain the default **easybook** styles
(`include_styles` option set to `true`) and then apply your custom CSS to 
include new styles or modify them. To do this, add a file called `style.css` 
within any of these directories:

  1. `<book>/Resources/Templates/<edition-name>/style.css`, if you want to
     apply the styles just to one specific edition.
  2. `<book>/Resources/Templates/<edtion-type>/style.css`, if you want to apply
     the same styles to all the editions of the same type (`epub`, `mobi`,
     `html`, `html_chunked` or `pdf`).
  3. `<book>/Resources/Templates/style.css`, if you want to apply the same
     styles to all the editions of the book.

Instead of creating this CSS file by hand, **easybook** includes a command called `customize` that generates the needed CSS for each edition. The first
argument of the command is the book directory and the second argument is the
name of the edition to be customized:

~~~ .cli
$ ./book customize my-book ebook

OK: You can now customize the book design with the following stylesheet:
<easybook-dir>/doc/my-book/Resources/Templates/ebook/style.css
~~~

The CSS file generated with the `customize` command is different for every 
edition format (`html`, `pdf`, `epub`, `mobi`) and it includes some comments 
to ease the customization of the book design.

## Fonts ##

### Default fonts ###

**easybook** bundles several free and high-quality fonts to make published 
books look professional. You can see the fonts and their license in the
`app/Resources/Fonts/` directory.

## Labels and titles ##

### Default labels and titles ###

**easybook** requires each content to have its own **title**. Define the title 
of each item with the `title` configuration option:

~~~ .yaml
book:
    # ...
    contents:
        - ...
        - { element: part, title: 'Introduction' }
        - { element: chapter, number: 1, content: chapter1.md }
        - { element: chapter, number: 2, content: chapter2.md }
        - { element: part, title: 'Advanced Concepts' }
        - { element: chapter, number: 3, content: chapter3.md }
        - ...
~~~

Chapters and appendices don't have to explicitly set their title in the
`config.yml` file. The reason is that **easybook** looks for the first `<h1>`
heading of the chapter or appendix and considers it as the title.

For the rest of items, if you don't set explicitly their titles, **easybook** 
assigns them a default title that varies depending on the language in which 
the book is written. The following are for example the default titles applied 
to English books (as defined in the `app/Resources/Translations/titles.en.yml`
file):

~~~ .yaml
title:
    acknowledgement: 'Acknowledgements'
    afterword:       'Afterword'
    author:          'About the author'
    conclusion:      'Conclusion'
    cover:           'Cover'
    dedication:      'Dedication'
    edition:         'About this edition'
    epilogue:        'Epilogue'
    foreword:        'Foreword'
    glossary:        'Glossary'
    introduction:    'Introduction'
    license:         'License'
    lof:             'List of Figures'
    lot:             'List of Tables'
    preface:         'Preface'
    prologue:        'Prologue'
    title:           'Title'
    toc:             'Table of contents'
~~~

Moreover, the book can add **labels** (`Chapter XX`, `Appendix XX`, etc.) to 
the section titles using the `labels` option. As explained before, this option 
indicates the content types for which labels are added automatically.

Unless you modify this option, books only add labels in chapters and 
appendices. The default labels also depend on the language in which the book 
is written. The labels applied for English books are defined in the
`app/Resources/Translations/labels.en.yml` file.

Unlike the titles, labels can contain variable parts, such as the appendix or
chapter number. Therefore, **easybook** uses Twig templates to define each 
label:

~~~ .yaml
label:
    figure: 'Figure {{ element.number }}.{{ item.number }}'
    part:   'Part {{ item.number }}'
    table:  'Table {{ element.number }}.{{ item.number }}'
~~~

The `figure` and `table` labels can use the same variables as the `figure.twig`
and `table.twig` templates explained previously in the
[default templates](#default-templates) section. Therefore, `{{ item.number }}`
shows the autogenerated number of the image/table and `{{ element.number }}` 
shows the number of the chapter or appendix.

Chapters and appendices must define six different labels, each corresponding to
one of the six heading levels (`<h1>`, `<h2>`, `<h3>`, `<h4>`, `<h5>` and
`<h6>`). In the following example, appendices only include a label in their 
titles, thus leaving empty the last five levels:

~~~ .yaml
label:
    appendix:
        - 'Appendix {{ item.number }}'
        - ''
        - ''
        - ''
        - ''
        - ''
~~~

Labels can access to all configuration options defined by the item in the
`config.yml` file. The label of a chapter can then use `{{ item.number }}` to
show the number of the chapter. In addition to these properties, label 
templates have access to two special variables called `level` and `counters`.

The `level` variable indicates the section heading level for which you want to
get the label, being `1` the level of `<h1>` heading and `6` the level of
`<h6>` headings. The `counters` variable is an array with the counters of all 
heading levels. For that reason, to show second-level headings as `1.1`, `1.2` 
... `7.1`, `7.2` you can use the following expression:

~~~ .yaml
label:
    chapter:
        - 'Chapter {{ item.number }}'
        - '{{ item.counters[0] }}.{{ item.counters [1] }}'
        - ...
~~~

Extending the previous example, you can use the following templates to format
all the heading levels as `1.1`, `1.1.1`, `1.1.1.1`, etc.:

~~~ .yaml
label:
    chapter:
        - 'Chapter {{ item.number }} '
        - '{{ item.counters[0:2]|join(".") }}'  # 1.1
        - '{{ item.counters[0:3]|join(".") }}'  # 1.1.1
        - '{{ item.counters[0:4]|join(".") }}'  # 1.1.1.1
        - '{{ item.counters[0:5]|join(".") }}'  # 1.1.1.1.1
        - '{{ item.counters[0:6]|join(".") }}'  # 1.1.1.1.1.1
~~~

This last example clearly shows what you can achieve by combining the
flexibility of **easybook** and the power of Twig.

### Custom labels and titles ###

If you want to use your own titles and labels, you must first create the
`Translations` directory inside the `Resources` directory of your book (you 
must also create the latter if it doesn't exist). Then add the new label file 
in one of the following directories:

   1. `<book>/Resources/Translations/<edition-name>/labels.en.yml`, if you want
      to change the labels in a single edition. The subdirectory of
      `Translations` must be named exactly like the edition being published.
   2. `<book>/Resources/Translations/<edition-type>/labels.en.yml`, if you want
      to change the labels in all editions of the same type. The directory in
      `Translations` can only be named `epub`, `mobi`, `pdf`, `html`, or
      `html_chunked`.
   3. `<book>/Resources/Translations/labels.en.yml`, if you want to change the
      labels on all the editions of the book, regardless of its name or type.

When you use your own label file, you don't have to define the value of all
labels. Add only the labels that you want to modify and **easybook** will 
assign to the rest their default values. Therefore, to only modify the label 
of the images in any book edition, create a new
`<book>/Resources/Translations/labels.en.yml` file and add just the following 
content:

~~~ .yaml
label:
    figure: 'Illustration {{ item.number }}'
~~~

If you want to change the titles instead of the labels, follow the same steps
but create a file called `titles.en.yml` instead of `labels.en.yml`. If your 
book isn't written in English, replace `en` for the code of the other language 
(e.g. `labels.es.yml` for Spanish labels).


[1]: http://twig.sensiolabs.org/
[2]: http://www.princexml.com/
[3]: http://www.princexml.com/doc/8.0/
