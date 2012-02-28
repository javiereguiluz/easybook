# ROADMAP #

## easybook 4.X ##

(in no particular order)

  * Consider a cache mechanism for not regenerating unchanged contents
  * Plugins should be able to register resources (external libs, assets, ...)
  * Add a new AdmonitionPlugin (adds support for `[note]`, `[tip]`, `[caution]`, `[sidebar]`)
  * Add a new FootnotePlugin (PrinceXML support them by default)
  * Add a new InternalLinkPlugin (internal links don't work on `html_chunked` books)
  * Default contents should be different for each supported language
  * Temp files (needed in `pdf` editions) should be created in app/Cache, not in OS temp/ dir.
  * Add new content type: `backcover`
  * Add a new `plugin` command to bootstrap plugin creation
  * Add support for multiple edition inheritance
  * Improve HTML editions design and UX
  * Add table captions for Markdown parser
  * Add PHPdoc to essential methods and classes

## easybook 4.2 (28-feb-2012) ##

  * [OK] Remove vendors from versioning and add composer.lock file
  * [OK] Package the application as -PHAR- ZIP file 
  * [OK] Add more unit tests
  * [OK] Add ePub format publisher
  * [OK] Code syntax highlighting (both in PDF and HTML books)
  * [OK] Add new content type: `lof` (list of figures)
  * [OK] Add new content type: `lot` (list of tables)
  * [OK] Addded eight new content types (`afterword`, `conclusion`, `epilogue`, `foreword`, `glossary`, `introduction`, `preface`, `prologue`)
  * [OK] Default contents are no longer necessary
  * [OK] Add counters for figures and tables
  * [OK] Improve label options (replace `auto_label` for `labels`)
  * [OK] Added tests for publishing command
  * [OK] Books should be able to use their own custom labels and titles

## easybook 4.0 (7-feb-2012) ##

  * [OK] Upgrade to Twig 1.6
  * [OK] Add a LICENSE.md file
  * [OK] Add a README.md file
  * [OK] Add support for: English, Spanish, French, Italian, Deutsch, Catalan and Basque
  * [NO] Add a new `sample` command to publish a demo book (to easily try easybook features)
  * [OK] Tweak templates and CSS styles for all the edition types
  * [OK] Tweak UX of `new` and `publish` commands