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

Regardless the installation mode, install dependencies via [composer](http://packagist.org/):

```
$ cd easybook
$ wget http://getcomposer.org/composer.phar
$ php composer.phar install
```

Then use **easybook** with the `book` command:

```
$ ./book
```

If the last command doesn't work, try `php book`

## Documentation ##

**easybook** is fully documented at http://easybook-project.org/doc/

## License ##

**easybook** is licensed under the MIT license.

## Tests ##

Execute the following command to test **easybook** (requires PHPUnit):

```
$ cd easybook
$ phpunit
```

Travis CI status: [![Travis CI status](https://secure.travis-ci.org/javiereguiluz/easybook.png?branch=master)](http://travis-ci.org/javiereguiluz/easybook)

