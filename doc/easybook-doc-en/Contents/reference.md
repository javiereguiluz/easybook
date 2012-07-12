# Markdown reference #

**easybook** uses Markdown as the standard markup language to create books and
contents. In the future, **easybook** will support other markup languages such
as reStructuredText. Meanwhile, **easybook** enhances the original Markdown
syntax to provide some of the most demanded features for book writers.

## Basic syntax ##

**easybook** supports all of the original features described in the
[official Markdown syntax](http://daringfireball.net/projects/markdown/syntax/).
Please, refer to that reference to learn how to define headers, paragraphs,
lists, code blocks, images, and so on.

## Extended syntax ##

**easybook** includes a Markdown parser based on [PHP Markdown Extra Project](http://michelf.com/projects/php-markdown/extra/).
This project brings some nice extra features to the basic Markdown syntax.

### Header id attributes ###

You can add custom `id` attributes to any header (both *setext* and *atx* style
headers):

    [code]
    (atx style headers)
    # Header 1 # {#header1}

    ...

    ## Header 2 ## {#my-custom-header2-id}

    (setex style headers)
    Other First Level Header {#special-id}
    ========================

Then, you can link internally to any book section:

    [code]
    In the [first section](#header1) you can see the differences between
    [this section](#my-custom-header2-id) and [that section](#special-id).

As a bonus, in PDF books the internal links display the linked book page.

### Tables ###

You can add leading and trailing pipes to table rows:

    [code]
    | First Header  | Second Header |
    | ------------- | ------------- |
    | Content Cell  | Content Cell  |
    | Content Cell  | Content Cell  |

The contents of the cells can be aligned adding a `:` character on the side
where you want contents aligned. In the following example, the contents of the
first column will be left-aligned and the contents of the second column will be
right-aligned:

    [code]
    | Item      | Value |
    | :-------- | -----:|
    | Computer  | $1600 |
    | Phone     |   $12 |
    | Pipe      |    $1 |

Table contents can include any simple formatting as bold, italics, code, etc.

### Other features ###

As explained in the [official PHP Markdown Extra syntax](http://michelf.com/projects/php-markdown/extra/)
you can also create definition lists, footnotes, automatic abbreviations, fenced
code blocks, etc.

## easybook exclusive syntax ##

### Code blocks ###

Code blocks can be automatically highlighted using the following syntax:

    [code]
    [code language]
    ...

    [code php]
    ...

    [code xml]
    ...

**easybook** recognizes automatically tens of programming languages thanks to
the use of [GeSHi highlighting library](http://qbnz.com/highlighter/).

### Image alignment ###

Books usually align/float images on the right/left of the contents, but Markdown
doesn't include a mechanism to define image alignment:

    [code]
    ![Alt text](url "Optional title text")

**easybook** defines a simple and Markdown-compatible mechanism based on adding
whitespaces on *Alt text*:

    [code]
    // regular image not aligned
    ![Test image](figure1.png)

    // "alt text" has a whitespace on the left
    // -> the image is left aligned
    ![ Test image](figure1.png)

    // "alt text" has a whitespace on the right
    // -> the image is right aligned
    ![Test image ](figure1.png)

    // "alt text" has whitespaces both on the left and on the right
    // -> the image is centered
    ![ Test image ](figure1.png)

If you enclose alt text with quotes, make sure that whitespaces are placed
outside the quotes. The following images for example don't define any alignment:

    [code]
    !["Test image"](figure1.png)

    ![" Test image"](figure1.png)

    !["Test image "](figure1.png)

    ![" Test image "](figure1.png)

Image alignment is also possible when using the alternative image syntax:

    [code]
    ![Test image][1]

    ![ Test image][1]

    ![Test image ][1]

    ![ Test image ][1]

    [1]: figure1.png
