# ROADMAP #

## easybook 4.X () ##

(in no particular order)

  * Code syntax highlighting (both in PDF and HTML books)
  * Consider a cache mechanism for not regenerating unchanged contents
  * Plugins should be able to register resources (external libs, assets, ...)
  * Add a new AdmonitionPlugin (adds support for `[note]`, `[tip]`, `[caution]`, `[sidebar]`)
  * Add a new FootnotePlugin (PrinceXML support them by default)
  * Add a new InternalLinkPlugin (internal links don't work on `html_chunked` books)
  * Default contents should be different for each supported language
  * Temp fles (need in `pdf` editions) should be created in app/Cache, not in OS temp/ dir.
  * Add new content type: `introduction`
  * Add new content type: `preface`
  * Add new content type: `lof` (list of figures)
  * Add new content type: `lot` (list of tables)
  * Add a new `plugin` command to bootstrap plugin creation
  * Books should be able to use their own custom labels and titles
  * Add counters for figures and tables
  * Add support for multiple edition inheritance
  * Add unit tests
  * Improve HTML editions design and UX
  * Add ePub format publisher

## easybook 4.0 (7-feb-2012) ##

  * [OK] Upgrade to Twig 1.6
  * [OK] Add a LICENSE.md file
  * [OK] Add a README.md file
  * [OK] Add support for: English, Spanish, French, Italian, Deutsch, Catalan and Basque
  * [NO] Add a new `sample` command to publish a demo book (to easily try easybook features)
  * [OK] Tweak templates and CSS styles for all the edition types
  * [OK] Tweak UX of `new` and `publish` commands