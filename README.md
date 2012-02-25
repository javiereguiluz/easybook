# easybook #

*«book publishing as easy as it should be»*

**easybook** is an application that lets you easily publish books in various electronic formats. It was originally designed to publish technical programming books, but you can use **easyboook** to publish any kind of book, manual or documentation.

**easybook** project website: <http://easybook-project.org>

## Installation ##

If you use Git:

```
$ mkdir easybook
$ git clone http://github.com/javiereguiluz/easybook.git easybook
```

If you don't use Git:

  1. `mkdir easybook`
  2. Download https://github.com/javiereguiluz/easybook/zipball/master ZIP file
  3. Uncompress the ZIP file in `easybook` directory

Regardless the installation mode, use **easybook** with the `book` command:

```
$ cd easybook
$ ./book
```

If the last command doesn't work, try `php book`

## Documentation ##

**easybook** is fully documented at http://easybook-project.org/doc/

## Examples ##

[easybook-examples](http://github.com/javiereguiluz/easybook-examples)
is a repository with several examples of how to use **easybook** to
produce advanced and high quality books. This is by far the best way
to learn **easybook**.

## License ##

**easybook** is licensed under the MIT license.

## Tests ##

Execute the following command to test **easybook** (requires PHPUnit):

```
$ cd easybook
$ phpunit
```

Travis CI status: [![Travis CI status](https://secure.travis-ci.org/javiereguiluz/easybook.png?branch=master)](http://travis-ci.org/javiereguiluz/easybook)

## Requirements ##

In order to generate PDF files, PrinceXML library must be installed. 
If you haven't installed it yet, you can download a fully-functional demo at: 

    http://www.princexml.com/download 

