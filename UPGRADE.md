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
