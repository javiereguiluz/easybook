# UPGRADE guide #

## Upgrade to easybook 4.2 ##

### 1. Books can now define their own labels and titles for each edition ###

Explained in the *[custom labels and titles](http://easybook-project.org/doc/chapter-2.html#custom-labels-and-titles)* section of the documentation.

### 2. auto_label configuration option no longer exists ###

This option enabled/disabled labels for all the elements types of the book:
chapters, appendices, figures, ...

This option has been replaced by the much more powerful `labels` option,
which defines the element types for which labels are added:

```yml
book:
    # ...
    editions:
        web:
            # no label for any element
            labels:  []
            # ...
        website:
            # labels are only added for chapter and appendices headings
            labels:  ['appendix', 'chapter']
            # ...
        print:
            # besides chapter and appendices headings, labels are also added
            # to tables and images captions
            labels: ['appendix', 'chapter', 'figure`, `table`]
```

`figure` and `table` aren't content types, but special values used only
in `labels` option.

### 3. Default labels for appendices have been modified ###

Appendix labels - before:

```yml
label:
    appendix: ['Appendix {{ item.number }} ', '', '', '', '', '']
```

Appendix labels - after:

```yml
label:
    appendix:
        - 'Appendix {{ item.number }}'
        - '{{ item.counters[0:2]|join(".") }}' # 1.1
        - '{{ item.counters[0:3]|join(".") }}' # 1.1.1
        - '{{ item.counters[0:4]|join(".") }}' # 1.1.1.1
        - '{{ item.counters[0:5]|join(".") }}' # 1.1.1.1.1
        - '{{ item.counters[0:6]|join(".") }}' # 1.1.1.1.1.1
```

### 4. Some label parameters have been renamed ###

The main parameters available for each label are now grouped under `item`
variable. Other special parameters may also exist for some labels, as explained
in the documentation.

Chapter labels - before:

```yml
label:
    chapter:
        - 'Chapter {{ item.number }} '
        - '{{ counters[0:2]|join(".") }}' # 1.1
        - '{{ counters[0:3]|join(".") }}' # 1.1.1
        - '{{ counters[0:4]|join(".") }}' # 1.1.1.1
        - '{{ counters[0:5]|join(".") }}' # 1.1.1.1.1
        - '{{ counters[0:6]|join(".") }}' # 1.1.1.1.1.1
```

Chapter labels - after:

```yml
label:
     chapter:
         - 'Chapter {{ item.number }} '
         - '{{ item.counters[0:2]|join(".") }}' # 1.1
         - '{{ item.counters[0:3]|join(".") }}' # 1.1.1
         - '{{ item.counters[0:4]|join(".") }}' # 1.1.1.1
         - '{{ item.counters[0:5]|join(".") }}' # 1.1.1.1.1
         - '{{ item.counters[0:6]|join(".") }}' # 1.1.1.1.1.1
```

Figure and image labels - before:

```yml
label:
    figure: 'Figure {{ item.number }}.{{ counter }}'
    table:  'Table {{ item.number }}.{{ counter }}'
```

Figure and image labels - after:

```yml
label:
    figure: 'Figure {{ element.number }}.{{ item.number }}'
    table:  'Table {{ element.number }}.{{ item.number }}'
```

### 5. Images are now decorated with their own Twig template ###

```jinja
<div class="figure">
    {{ item.content }}

{% if item.caption != '' %}
    <p class="caption"><strong>{{ item.label }}</strong> {{ item.caption }}</p>
{% endif %}
</div>
```

You can tweak the previous design creating a `figure.twig` template in your
own theme. If you want to maintain the previous no-decoration design, just
create in your book the template `Resources/Templates/figure.twig` with the
following content:

```jinja
{{ item.content }}
```

### 6. Tables are now decorated with their own Twig template ###

```jinja
<div class="table">
{% if item.caption != '' %}
    <p class="caption"><strong>{{ item.label }}</strong> {{ item.caption }}</p>
{% endif %}

    {{ item.content }}
</div>
```

You can tweak the previous design creating a `table.twig` template in your
own theme. If you want to maintain the previous no-decoration design, just
create in your book the template `Resources/Templates/table.twig` with the
following content:

```jinja
{{ item.content }}
```

### 7. It's no longer necessary to define a default content ###

Previous versions of **easybook** required that every content type had a default
content, just in case book config hadn't defined it. This also simplifies the creation
of custom content types.

Now **easybook** only defines sensible default contents for `title`, `license`
and `edition` content type. The other empty defeault files have been deleted.

Documentation has been updated to better explain how to define a custom content type.

### 8. Added eight new content types ###

**easybook** has added eight new content types (`afterword`, `conclusion`, `epilogue`,
`foreword`, `glossary`, `introduction`, `preface`, `prologue`).

This means that you no longer need to define a custom content type if you need any of the
new content types. Moreover, eight new default labels, titles and templates have been defined
for the new contents.

### 9. Added list-of-figures and list-of-tables content types ###

**easybook** has added two new content types: `lof` (list of figures) and `lot` (list of tables).
You can easily add these types on your books:

```yaml
book:
    # ...
    contents:
        # ...
        - { element: lof }
        - { element: lot }
```

`figure.twig` and `table.twig` have been modified to add an `id` attribute necessary for
linking tables and images.

Two new templates have been created: `lof.twig` and `lot.twig` and two new default titles
have been defined for all supported languages.

### 10. Code listings are now decorated with their own Twig template ###

```jinja
<div class="code {{ item.language }}">
{{ item.content }}
</div>
```

You can tweak the previous design creating a `code.twig` template in your
own theme. If you want to maintain the previous no-decoration design, just
create in your book the template `Resources/Templates/code.twig` with the
following content:

```jinja
{{ item.content }}
```

### 11. Added syntax highlighting for code listings ###

**easybook** now can highlight any code listing. Just add the programming, markup
or configuration language as the first line of the listing.

Code listing with no syntax highlighting:

```
public function onStart(BaseEvent $event)
{
    $event->app->set('app.timer.start', microtime(true));
}
```

Code listing with syntax highlighting:

```
[php]
public function onStart(BaseEvent $event)
{
    $event->app->set('app.timer.start', microtime(true));
}
```

**easybook** uses the [GeSHi library](http://qbnz.com/highlighter) to highlight
the code listings. Therefore, it supports more than 200 programming languages
​(`[php]`, `[java]`, `[c]`, `[javascript]`, `[ruby]`, `[python]`, `[perl]`,
`[erlang]`, `[haskell]`, ...), markup languages ​​(`[html]`, `[yaml]`, `[xml]`, ...)
and configuration (`[ini]`, `[apache]`, ...).

### 12. Added ePub support ###

Read the updated documentation for details, but in short you can now
define editions of type `epub` which generates `.epub` version 2 books:

```yaml
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
```

