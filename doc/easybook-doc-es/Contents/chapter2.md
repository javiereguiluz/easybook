# Publicando tu segundo libro #

En el capítulo anterior se explica cómo crear y publicar fácilmente un libro
con **easybook**. No obstante, apenas se mencionan algunas opciones de
configuración y no se explican sus características más avanzadas. Este
capítulo explica todos los tipos de contenidos disponibles, cómo crear y
modificar las ediciones de un libro y cómo controlar su aspecto mediante las
plantillas.

## Tipos de contenido ##

Los contenidos del libro se definen en la opción de configuración `contents`
del archivo `config.yml`. Al crear un nuevo libro con el comando `new` sus
contenidos por defecto son los siguientes:

    [yaml]
    book:
        # ...
        contents:
            - { element: cover }
            - { element: toc   }
            - { element: chapter, number: 1, content: chapter1.md }
            - { element: chapter, number: 2, content: chapter2.md }

La opción más importante de cada contenido es `element`, que define el tipo
de contenido que se trata. **easybook** soporta actualmente 21 tipos de
contenidos (las siguientes definiciones se han obtenido de la Wikipedia):

  * `acknowledgement` o *agradecimientos*, en ocasiones se incluye como parte
  del prefacio, en vez de como sección independiente. Se utiliza para que el
  autor reconozca a todas las personas que le han ayudado en la elaboración
  del libro.
  * `afterword` o *epílogo*,  se emplea para describir un momento muy
  posterior al marco temporal en el que se desarrolla el contenido principal
  de la obra.
  * `appendix` o *apéndice*, ofrece información suplementaria al contenido
  principal de la obra. En ocasiones corrige errores o aclara las
  inconsistencias. Otras veces amplía o actualiza el contenido de los
  capítulos.
  * `author` o *autor*, incluye información sobre el autor/autores de la obra.
  * `chapter` o *capítulo*, es el tipo de contenido más utilizado normalmente
  en los libros.
  * `conclusion` o *conclusiones*, última parte del contenido de la obra,
  donde se resuelven todos los temas pendientes o donde se fijan las ideas y
  pensamientos.
  * `cover` o *portada*, es la portada frontal principal del libro.
  * `dedication` o *dedicatoria*, página que normalmente precede al comienzo
  del contenido del libro y en la que el autor indica la persona o personas
  para las que ha escrito el libro.
  * `edition` o *edición*, muestra información sobre la edición actual del
  libro, incluyendo su fecha de publicación.
  * `epilogue` o *epílogo*, contenido que se incluye al final de una obra y
  sirve para *cerrar* sus contenidos.
  * `foreword` o *preámbulo*, habitualmente escrito por otra persona
  diferente al autor principal de la obra. Normalmente trata sobre la
  relación entre la persona que escribe el preámbulo y el autor de la obra o
  su contenido.
  * `glossary` o *glosario*, consiste en una serie de definiciones de las
  palabras o términos más importantes de la obra, normalmente por orden
  alfabético.
  * `introduction` o *introducción*, sección inicial de la obra que indica su
  propósito u objetivos.
  * `license` o *licencia*, incluye información sobre el *copyright* de la
  obra o sobre cualquier otro derecho del autor o del editor que afecte a la
  obra.
  * `lof` de *list of figures* o *lista de figuras*, muestra un listado
  ordenado de todas las figuras e ilustraciones de la obra, junto con su
  número y título (en las ediciones de tipo `pdf` también se muestra la
  página en la que se encuentra la imagen).
  * `lot` de *list of tables* o *lista de tablas*, muestra un listado
  ordenado de todas las tablas de la obra (en las ediciones de tipo `pdf`
  también se muestra la página en la que se encuentra la tabla).
  * `part` o *sección*, se emplean normalmente para agrupar capítulos o
  apéndices relacionados.
  * `preface` o *prefacio*, explica cómo se creó el libro o cómo surgió la
  idea de escribirlo.
  * `prologue` o *prólogo*, normalmente está *escrito* por el narrador de la
  obra o por algún otro personaje. Se trata de una introducción a la historia
  principal de la obra e incluye detalles e información relacionados pero
  previos al marco temporal en el que de desarrolla la historia principal.
  * `title` o *portada interior*, es la primera página interior del libro y
  normalmente muestra el título de la obra, el nombre de su autor y la
  edición actual.
  * `toc` o *índice de contenidos*, se trata de un listado de los contenidos
  principales mostrados en el mismo orden en el que se incluyen en la obra.

Salvo `appendix`, `chapter` y `part` el resto de contenidos normalmente no
requieren ninguna opción adicional:

    [yaml]
    book:
        # ...
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

Los contenidos `appendix` y `chapter` admiten las siguientes opciones:

  * `number` o número del capítulo/appendix. Se utiliza para crear las
  etiquetas que acompañan a cada título de sección (`1.1`, `1.2`, `1.2.1`,
  `1.2.2`, etc.) **easybook** no restringe su formato, por lo que puedes
  utilizar números romanos (`I.1`, `I.2`), letras (`A.1`, `A.2`) o cualquier
  otro símbolo o cadena de texto.
  * `content` o nombre del archivo que tiene los contenidos de este elemento.
  El nombre del archivo debe incluir su extensión (`.md` en el caso de
  Markdown). El valor de esta opción se interpreta como la ruta a partir del
  directorio `Contents/` del libro, por lo que puedes incluir todos los
  subdirectorios que quieras.

El contenido `part` admite la siguiente opción:

  * `title` o título de la sección. En un libro impreso, una sección
  simplemente es una página que separa unos capítulos de otros. En un libro
  web, la sección se muestra como un título de sección muy destacado.

Los 21 tipos de contenidos de **easybook** son suficientes para publicar la
mayoría de libros, pero si lo necesitas, el siguiente capítulo explica cómo
crear nuevos tipos de contenido.

## Ediciones ##

**easybook** es tan flexible que permite publicar un mismo libro de formas
radicalmente diferentes. Esto es posible gracias a las **ediciones**, que
definen las características concretas con las que se publica el libro.

Las ediciones se definen bajo la opción `editions` en el archivo `config.yml`.
Por defecto los libros creados con el comando `new` disponen de cuatro ediciones
llamadas `ebook`, `print`, `web` y `website` con las siguientes opciones:

    [yaml]
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

            print:
                format:         pdf
                isbn:           ~
                labels:         ['appendix', 'chapter']  # labels also available for: 'figure', 'table'
                margin:
                    top:        25mm
                    bottom:     25mm
                    inner:      30mm
                    outter:     20mm
                page_size:      A4
                toc:
                    deep:       2
                    elements:   ['appendix', 'chapter']
                two_sided:      true

            web:
                format:         html
                highlight_code: true
                include_styles: true
                labels:         ['appendix', 'chapter']  # labels also available for: 'figure', 'table'
                toc:
                    deep:       2
                    elements:   ['appendix', 'chapter']

            website:
                extends:        web
                format:         html_chunked

El nombre de las ediciones debe ser único para un mismo libro y no puede
contener espacios en blanco. Este mismo nombre se utiliza como subdirectorio
dentro del directorio `Output/` del libro para distinguir los contenidos de
cada edición. Puedes crear tantas ediciones como quieras, pero todas pertenecen
a alguno de los cuatro tipos siguientes definidos por **easybook** mediante la
opción `format`:

  * `epub`, el libro se publica como un e-book llamado `book.epub`
  * `pdf`, el libro se publica como un archivo PDF llamado `book.pdf`
  * `html`, el libro se publica como una página HTML llamada `book.html`
  * `html_chunked`, el libro se publica como un sitio web estático en un
  directorio llamado `book`

Cada tipo de edición define sus propias opciones de configuración. Los tipos
`epub`, `html` y `html_chunked` disponen de las mismas opciones:

  * `highligh_code`, si vale `true` se colorea la sintaxis de los listados de
  código del libro (esta opción todavía no se tiene en cuenta).
  * `include_styles`, si vale `true` se aplican los estilos CSS por defecto de
  **easybook**.
  * `labels`, indica los tipos de elementos para los que se añaden etiquetas en
  sus títulos de sección. Por defecto sólo se aplican a los capítulos y
  apéndices. Además de los tipos de contenido de **easybook**, puedes utilizar
  dos valores especiales llamados `figure` y `table`, que añaden respectivamente
  etiquetas a las imágenes y las tablas del libro. Si no quieres mostrar ninguna
  etiqueta, elimina todos los contenidos de esta opción: `labels: []`.
  * `toc`, establece las opciones del índice de contenidos. Sólo se tiene en
  cuenta si el libro incluye un contenido de tipo `toc`. A su vez, dispone de
  dos subopciones:
    * `deep`, indica el nivel de título máximo que se incluye en el índice (`1`
    es el valor más pequeño posible y equivale a mostrar sólo los títulos de
    nivel `<h1>`; el valor más grande es `6` y equivale a mostrar todos los
    títulos `<h1>`, `<h2>`, `<h3>`, `<h4>`, `<h5>` y `<h6>`).
    * `elements`, indica el tipo de contenidos que se incluyen en el índice
    (por defecto sólo se muestran, si las hay, las secciones y los títulos de
    apéndices y capítulos).

Por su parte, el tipo de edición `pdf` dispone de las siguientes opciones:

  * `isbn`, idica el código ISBN-10 o ISBN-13 del libro impreso (esta opción
  todavía no se tiene en cuenta).
  * `include_styles`, si vale `true` el libro PDF se maqueta con los estilos por
  defecto de **easybook**. Así podrás crear libros bonitos sin esfuerzo. En el
  próximo capítulo se explica cómo añadir también tus propios estilos.
  * `labels`, mismo significado y opciones que en las ediciones de tipo `epub`,
  `html` y `html_chunked`.
  * `margin`, permite indicar los cuatro márgenes de las páginas del archivo
  PDF: superior (`top`), inferior (`bottom`), interior (`inner`) y exterior
  (`outter`). En los libros impresos a una cara, los márgenes interior y
  exterior se interpretan respectivamente como margen izquierdo y derecho. Los
  valores de los márgenes se pueden indicar con cualquier unidad de medida
  válida en CSS (`25mm`, `2.5cm`, `1in`).
  * `page_size`, indica el tamaño de página del archivo PDF. Gracias al uso de
  PrinceXML **easybook** soporta decenas de [tamaños de página](http://www.princexml.com/doc/7.1/page-size/):
  `A4`, `A3`, `US-Letter`, `US-Legal`, `crown-quarto`, etc.
  * `toc`, mismo significado y opciones que en las ediciones de tipo `epub`,
  `html` y `html_chunked`.
  * `two_sided`, si vale `true` el PDF está preparado para imprimirlo a doble
  cara. Si vale `false`, se prepara para imprimirlo a una cara.

Una última opción de configuración muy útil y disponible en todos los tipos de
edición es `extends`. El valor de esta opción indica el nombre de la edición de
la que *hereda* o extiende una edición. Cuando una edición *hereda* de otra, se
copia el valor de todas sus opciones, que posteriormente se pueden redefinir en
la *edición hija*.

Imagina por ejemplo que quieres publicar en PDF un mismo libro modificando
ligeramente su aspecto. La versión borrador (`draft`) se publica a doble cara y
con unos márgenes muy pequeños para ahorrar papel, la versión normal (`print`)
se publica a una cara y con unos márgenes normales. La versión para publicar en
el sitio lulu.com (`lulu`) es parecida a la versión normal, pero se publica a
doble cara:

    [yaml]
    book:
        # ...
        editions:
            print:
                format:       pdf
                isbn:         ~
                labels:       ['appendix', 'chapter']  # labels also available for: 'figure', 'table'
                margin:
                    top:      25mm
                    bottom:   25mm
                    inner:    30mm
                    outter:   20mm
                page_size:    A4
                toc:
                    deep:     2
                    elements: ['appendix', 'chapter']
                two_sided:    false

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

La única limitación de la herencia es que sólo puede ser de un nivel, ya que
una edición no puede heredar de otra que a su vez herede de una tercera.

## Temas ##

Se denomina **tema** al conjunto de plantillas, hojas de estilo y otros recursos
que definen el aspecto de los contenidos del libro. **easybook** ya incluye un
tema para cada tipo de edición (`epub`, `pdf`, `html`, `html_chunked`), por lo
que tus libros se verán muy bien sin ningún esfuerzo. 

Los temas por defecto se encuentran en el directorio `app/Resources/Themes/`.
Si quieres modificar el aspecto de tus libros, **no** toques los archivos de
estos directorios. En el siguiente capítulo se explica cómo redefinir
fácilmente para tu libro cualquier plantilla o recurso.

### Contenidos por defecto ###

En la mayoría de libros, los únicos elementos que definen de sus propios
contenidos son los capítulos y los apéndices (utilizando la opción `content`).
Por eso **easybook** define contenidos por defecto sensatos para algunos tipos
de elementos. Así por ejemplo, si en el libro añades una portada interna
(contenido de tipo `title`) sin indicar su archivo de contenidos:

    [yaml]
    book:
        # ...
        contents:
            - ...
            - { element: title }
            - ...

**easybook** utiliza lo siguiente como contenido de este elemento:

    [twig]
    <h1>{{ book.title }}</h1>
    <h2>{{ book.author }}</h2>
    <h3>{{ book.edition }}</h3>

La portada interna por defecto simplemente muestra el título del libro, el
nombre del autor o autores y la edición del libro. Todos estos valores se
configuran bajo la opción `book` del archivo `config.yml`.

Los contenidos por defecto dependen tanto del elemento como del tipo de edición.
Puedes observar estos contenidos en el directorio `Contents/` del tema.

### Contenidos propios ###

Si no quieres utilizar el contenido por defecto de **easybook** para algún
elemento, simplemente añade la opción `content` e indica el archivo que define
sus contenidos:

    [yaml]
    book:
        # ...
        contents:
            - ...
            - { element: license, content: creative-commons.md }
            - { element: title, content: mi-portada-interna.md }
            - ...

### Plantillas por defecto ###

El contenido de cada elemento se *decora* con una plantilla antes de incluirlo
en el libro definitivo. Las plantillas de **easybook** se crean con
[Twig](http://twig.sensiolabs.org/), el mejor lenguaje de plantillas para PHP.
Puedes ver todas las plantillas por defecto en el directorio `Templates/` del
tema.

Observa por ejemplo la plantilla utilizada para decorar cada capítulo de un
libro PDF:

    [twig]
    <div class="page:chapter new-page">

    <h1 id="{{ item.slug }}"><span>{{ item.label }}</span> {{ item.title }}</h1>

    {{ item.content }}

    </div>

Los datos del contenido que se decora se encuentran en una variable llamada
`item`. A continuación se muestran las propiedades de esta variable:

  * `item.title`, título del elemento. Obtenido a través de su opción de
  configuración `title` o bien determinado automáticamente mediante los títulos
  por defecto que define **easybook**.
  * `item.slug`, es una versión del título que no incluye ni espacios en blanco
  ni otros *caracteres problemáticos* (eñes, acentos, comas, etc.) Su valor se
  utiliza en URL, como valor de atributos `id` de HTML, etc.
  * `item.label`, etiqueta del título principal del elemento. Normalmente sólo
  está definida para los capítulos (`Capítulo XX`), secciones (`Sección XX`) y
  apéndices (`Apéndice XX`).
  * `toc`, array con el índice de contenidos del elemento. Está vacía para la
  mayoría de elementos (`cover`, `license`, etc.) pero puede ser muy grande si
  se trata de un capítulo o apéndice complejo.
  * `item.content`, contenido del elemento listo para mostrarlo en el libro
  (ya se ha convertido desde su original en Markdown).
  * `item.original`, contenido original del elemento sin procesar ni manipular.
  * `item.config`, array con todas las opciones del elemento definidas en el
  archivo `config.yml`. Sus propiedades internas son `number`, `title`,
  `content` y `element`. Así por ejemplo, para determinar el tipo de elemento
  puedes utilizar la expresión `{{ item.config.element }}`.

Las imágenes y tablas se decoran con unas plantillas especiales llamadas
`figure.twig` y `table.twig` que tienen acceso a las siguientes variables:

  * `item.caption`, es el título de la imagen/tabla tal y como se indicó en el 
  contenido original.
  * `item.content`, es el código HTML completo generado para mostrar la
  imagen/tabla en el libro (`<img src="..." alt="..." />` o
  `<table> ... </table>`).
  * `item.label`, es la etiqueta de la imagen/tabla. Su valor es vacío a menos
  que la opción `labels` de la edición incluya el valor `figure` y/o `table`.
  * `item.number`, es el número autogenerado de la imagen/tabla dentro del
  elemento que se está procesando. La primera imagen/tabla se considera que es
  la número `1` y las siguientes se incrementan en una unidad.
  * `element.number`, es el número del elemento (capítulo, apéndice, etc.) en el
  que está incluida la imagen/tabla. Así por ejemplo, esta propiedad valdrá `5`
  para todas las imágenes/tablas incluidas dentro del capítulo `5` del libro.

Además de estas propiedades, todas las plantillas, independientemente de su
tipo, disponen de las siguientes tres variables globales:

  * `book`, proporciona acceso directo a todas las opciones de configuración
  definidas bajo `book` en el archivo `config.yml`. Así por ejemplo, puedes
  obtener el autor en cualquier plantilla mediante `{{ book.author }}` y la
  versión de **easybook** mediante `{{ book.generator.version }}`.
  * `edition`, proporciona acceso directo a todas las opciones de configuración
  de la edición que se está publicando actualmente.
  * `app`, proporciona acceso directo a todas las opciones de configuración y
  servicios de la propia aplicación **easybook** (definidos en el archivo
  `Application.php` del directorio `src/DependencyInjection/`).

### Plantillas propias ###

En el siguiente capítulo se explica con detalle cómo utilizar tus propias
plantillas en vez de las de **easybook**

### Estilos por defecto ###

El aspecto de los libros se controla mediante hojas de estilo CSS, incluso en
el caso de las ediciones de tipo `epub` y `pdf`. Los estilos por defecto se
encuentran en el directorio `Templates/` del tema que utiliza la edición. El
uso de CSS en las ediciones de tipo `epub` está bastante limitado debido al
lamentable soporte que ofrecen la mayoría de lectores de libros electrónicos.

En el caso de las ediciones `html` y `html_chunked`, el único límite es la
versión de CSS que puedas utilizar en tu sitio web (CSS 2.1, CSS 3, CSS 4, etc.)
ya que todos los navegadores modernos ofrecen un excelente soporte de CSS.

En el caso de la edición de tipo `pdf` el único límite es la imaginación. Los
libros PDF se generan mediante la aplicación [PrinceXML](http://www.princexml.com/)
convirtiendo un documento HTML en un archivo PDF con ayuda de una hoja de
estilos CSS. Lo mejor de PrinceXML es que define decenas de nuevas propiedades
CSS que no existen en el estándar y que permiten hacer cosas que simplemente
parecen imposibles. Te recomendamos que eches un vistazo al archivo
`styles.css.twig` del tema `Pdf/` para aprender algunas de las características
más avanzadas. ¡Vas a alucinar! Y no olvides echar un vistazo también a la
[ayuda de PrinceXML](http://www.princexml.com/doc/8.0/).

### Estilos propios ###

En el siguiente capítulo se explica con detalle cómo utilizar tus propios
estilos CSS en vez de los de **easybook**.

### Fuentes por defecto ###

Para que los libros publicados tengan un aspecto profesional, **easybook**
incluye y utiliza varias fuentes libres y gratuitas de alta calidad. Puedes ver
las fuentes y consultar la licencia de cada una en el directorio
`app/Resources/Fonts/`.

### Títulos y etiquetas por defecto ###

**easybook** requiere para su funcionamiento que cada contenido disponga de su
propio título. En el caso de los capítulos y apéndices, el título se obtiene a
partir del primer título de sección de su contenido. En otros casos como el de
las secciones, el título se define mediante la opción `title` en el archivo
`config.yml`. Al resto de contenidos que no definen o incluyen un título,
**easybook** les asigna un título por defecto que varía en función del idioma
en el que está escrito el libro. Puedes ver los títulos que se aplican a los
libros escritos en español en el archivo `app/Resources/Translations/titles.es.yml`.

Por otra parte, el libro puede añadir etiquetas (`Capítulo XX`, `Apéndice XX`,
etc.) a los títulos de sección mediante la opción `labels`. Como se explicó
anteriormente, esta opción indica el tipo de elementos para los que se añaden
las etiquetas automáticamente. Si no modificas su valor, el libro solamente
añade etiquetas en los capítulos y apéndices. Los valores por defecto de las
etiquetas en español se encuentran en el archivo
`app/Resources/Translations/labels.es.yml`.

A diferencia de los títulos, las etiquetas contienen partes variables, como por
ejemplo el número de apéndice o capítulo. Por ello, **easybook** define cada
etiqueta mediante una pequeña plantilla Twig:

    [yaml]
    label:
        figure: 'Figura {{ element.number }}.{{ item.number }}'
        part:   'Sección {{ item.number }}'
        table:  'Tabla {{ element.number }}.{{ item.number }}'

Las etiquetas `figure` y `table` tienen accceso a las mismas variables que las
plantillas `figure.twig` y `table.twig` explicadas anteriormente. Así que
`{{ item.number }}` muestra el número autogenerado para cada imagen/tabla y
`{{ element.number }}` muestra el número de capítulo o apéndice.

En el caso de los apéndices y capítulos, es necesario definir seis etiquetas,
cada una perteneciente a uno de los seis niveles de título (`<h1>`, `<h2>`,
`<h3>`, `<h4>`, `<h5>` y `<h6>`). El siguiente ejemplo hace que los apéndices
sólo muestren una etiqueta en su título, por lo que dejan vacíos los cinco
últimos niveles:

    [yaml]
    label:
        appendix:
            - 'Apéndice {{ item.number }}'
            - ''
            - ''
            - ''
            - ''
            - ''

Las etiquetas tienen acceso a todas las opciones de configuración definidas por
el elemento en el archivo `config.yml`. Así que la etiqueta de un capítulo
puede utilizar por ejemplo `{{ item.number }}` para mostrar el número del
capítulo. Además de todas estas propiedades, las etiquetas disponen de dos
propiedades especiales llamadas `counters` y `level`.

La propiedad `level` indica el nivel de título de sección para el que se quiere
obtener la etiqueta, siendo `1` el nivel correspondiente al título `<h1>` y así
hasta el `6` que equivale al título de nivel `<h6>`. La propiedad `counters` es
un array con los contadores de todos los títulos de sección encontrados hasta
ese punto. Así, para hacer que las secciones de segundo nivel de los capítulos
se muestren como `1.1`, `1.2`, ..., `7.1`, `7.2` puedes utilizar la siguiente
expresión:

    [yaml]
    label:
        chapter:
            - 'Capítulo {{ item.number }} '
            - '{{ item.counters[0] }}.{{ item.counters[1] }}'
            - ...

Generalizando el ejemplo anterior, puedes utilizar el siguiente código Twig
para hacer que las etiquetas de los capítulos sean de tipo `1.1`, `1.1.1`,
`1.1.1.1`, etc.:

    [yaml]
    label:
        chapter:
            - 'Capítulo {{ item.number }} '
            - '{{ item.counters[0:2]|join(".") }}'  # 1.1
            - '{{ item.counters[0:3]|join(".") }}'  # 1.1.1
            - '{{ item.counters[0:4]|join(".") }}'  # 1.1.1.1
            - '{{ item.counters[0:5]|join(".") }}'  # 1.1.1.1.1
            - '{{ item.counters[0:6]|join(".") }}'  # 1.1.1.1.1.1

Este último ejemplo da una buena idea de todo lo que puedes conseguir aunando
la flexibilidad de **easybook** y la potencia de Twig.

### Títulos y etiquetas propias ###

Para utilizar tus propios títulos y etiquetas, debes crear en primer lugar el
directorio `Translations` dentro del directorio `Resources` del libro (crea
también este último si no existe). Después, añade el nuevo archivo de etiquetas
en uno de los siguientes directorios:

  1. `<libro>/Resources/Translations/<nombre-edicion>/labels.es.yml`, si quieres
  modificar las etiquetas en una única edición. El directorio dentro de
  `Translations` debe llamarse exactamente igual que la edición que se está
  publicando.
  2. `<libro>/Resources/Translations/<tipo-edicion>/labels.es.yml`, si quieres
  modificar las etiquetas en todas las ediciones de un mismo tipo. El directorio
  dentro de `Translations` sólo puede llamarse `epub`, `html`, `html_chunked` o
  `pdf`.
  3. `<libro>/Resources/Translations/labels.es.yml`, si quieres modificar las
  etiquetas en todas las ediciones del libro, sin importar ni su nombre ni su
  tipo.

Cuando creas un archivo propio de etiquetas no es necesario que definas el
nuevo valor de todas las etiquetas. Añade solamente las que quieres modificar y
al resto **easybook** les asigna sus valores por defecto. Así por ejemplo, para
cambiar solamente la etiqueta de las imágenes en cualquier edición, crea el
archivo `<libro>/Resources/Translations/labels.es.yml` y añade lo siguiente:

    [yaml]
    label:
        figure: 'Ilustración {{ item.number }}'

Si quieres modificar los títulos en vez de las etiquetas, sigue los pasos
anteriores pero crea un archivo llamado `titles.es.yml` en vez de
`labels.es.yml`. Si tu libro está escrito en otro idioma que no sea español,
reemplaza el valor `es` por el código del otro idioma (ejemplo: `labels.en.yml`
para las etiquetas en inglés).
