easybook
========

*«book publishing as easy as it should be»*

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/34c47e7f-a523-4702-8310-ebec02a6a241/mini.png)](https://insight.sensiolabs.com/projects/34c47e7f-a523-4702-8310-ebec02a6a241) [![Travis CI status](https://secure.travis-ci.org/javiereguiluz/easybook.png?branch=master)](http://travis-ci.org/javiereguiluz/easybook) [![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/javiereguiluz/easybook/badges/quality-score.png?s=90c6ed79f22c90ee2c4761937b58ebe9c6b68889)](https://scrutinizer-ci.com/g/javiereguiluz/easybook/) [![Coverage Status](https://coveralls.io/repos/javiereguiluz/easybook/badge.svg?branch=master)](https://coveralls.io/r/javiereguiluz/easybook?branch=master)

**[easybook](http://easybook-project.org)** lets you easily publish books in
various electronic formats (ePub, MOBI, PDF and HTML). It was originally
designed to publish programming books, but you can use **easybook** to
publish any kind of book, manual or documentation website.

![easybook worflow diagram](doc/easybook-doc-en/Contents/images/what_is_easybook.png)

Installation
------------

Make sure to have installed [Composer](https://getcomposer.org/) globally in
your system and execute the following command:

```bash
$ composer create-project easybook/easybook easybook
```

Once installed, use **easybook** with the `book` command:

```
$ cd <easybook-installation-dir>
$ ./book
```

If the last command doesn't work, try `php book` or check `book` script
permissions.

Documentation
-------------

**easybook** is fully documented at http://easybook-project.org/documentation

License
-------

**easybook** is licensed under the [MIT license](LICENSE.md).

Tests
-----

Execute the following command to test **easybook** (it requires PHPUnit):

```
$ cd <easybook-installation-dir>
$ phpunit
```

Requirements
------------

In order to generate PDF files, PrinceXML library must be installed.
If you haven't installed it yet, you can download a fully-functional demo at:

    http://www.princexml.com/download

In order to generate MOBI files, KindleGen library must be installed.
If you haven't installed it yet, you can download it freely at Amazon:

    http://amzn.to/kindlegen
