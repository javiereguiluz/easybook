# Referencia de Markdown #

**easybook** utiliza Markdown como el lenguaje de marcado estándar para crear
libros y contenidos. En el futuro, **easybook** también soportará otros
lenguajes, como por ejemplo reStructuredText. Por el momento, **easybook** está
mejorando la sintaxis original de Markdown para suplir algunas de las
características más demandadas por los autores de contenidos.

## Sintaxis básica ##

**easybook** sorpota todas las características originales descritas en la
[sintaxis oficial de Markdown](http://daringfireball.net/projects/markdown/syntax/)
(en inglés). Consulta esa referencia para aprender cómo se definen los títulos
de sección, los párrafos, las listas, los bloques de código, las imágenes, etc.

## Sintaxis extendida ##

**easybook** utiliza un *parser* basado en el del [proyecto PHP Markdown Extra](http://michelf.com/projects/php-markdown/extra/).
Este proyecto añade algunas características muy útiles a la sintaxis de
Markdown.

### Atributos id en los títulos ###

Se pueden añadir atributos `id` propios en cualquier título de sección (tanto en
el estilo *setext* como en el estilo *atx*):

    [code]
    (títulos estilo atx)
    # Título 1 # {#titulo1}

    ...

    ## Título 2 ## {#mi-propio-id-para-titulo-2}

    (títulos estilo setex)
    Otro Título de Primer Nivel {#id-especial}
    ===========================

Después, puedes utilizar estos `id` para enlazar a cualquier sección del libro:

    [code]
    En la [primera sección](#titulo1) puedes observar las diferencias entre
    [esta sección](#mi-propio-id-para-titulo-2) y la [otra sección](#id-especial).

Además, los libros en formato PDF muestran los enlaces internos como el número
de página de la sección.

### Tablas ###

Puedes añadir el carácter `|` al principio y al final de cada fila de la tabla
para hacer más clara su sintaxis:

    [code]
    | Primera Cabecera  | Segunda Cabecera |
    | ----------------- | ---------------- |
    | Contenido 1-1     | Contenido 1-2    |
    | Contenido 2-1     | Contenido 2-2    |

Los contenidos de las celdas se pueden alinear añadiendo el carácter `:`  en el
lado en el que quieres alinearlos. En el siguiente ejemplo, los contenidos de la
primera columna se alinean a la izquierda y los de la segunda columna a la
derecha:

    [code]
    | Producto    | Precio |
    | :---------- | ------:|
    | Producto 1  |   1600 |
    | Producto 2  |    350 |
    | Producto 3  |     10 |

Por último, los contenidos de la tabla también se pueden formatear con negritas,
cursivas, código, etc.

### Otras características ###

La guía oficial de la [sintaxis de PHP Markdown Extra](http://michelf.com/projects/php-markdown/extra/)
(en inglés) explica cómo crear listas de definición, notas al pie de página,
abreviaturas automáticas, bloques de código alternativos, etc.

## Sintaxis propia de easybook ##

### Bloques de código ###

El contenido de los bloques de código se puede resaltar automáticamente si
utilizas la siguiente sintaxis:

    [code]
    [code lenguaje]
    ...

    [code php]
    ...

    [code xml]
    ...

**easybook** reconoce decenas de lenguajes de programación gracias al uso
interno de la [librería GeSHi](http://qbnz.com/highlighter/).

### Alineación de imágenes ###

Los libros normalmente alinean/flotan las imágenes a la derecha/izquierda de los
contenidos, pero Markdown no incluye ninguna sintaxis para definir la alineación
de las imágenes:

    [code]
    ![Texto del atributo alt](url "Texto opcional para el título")

**easybook** define un mecanismo muy sencillo y compatible con Markdown que se
basa en añadir espacios en blanco al texto del atributo `alt`:

    [code]
    // imagen normal no alineada
    ![Imagen de prueba](figura1.png)

    // hay un espacio en blanco a la izquierda del atributo 'alt'
    // -> la imagen se alinea a la izquierda
    ![ Imagen de prueba](figura1.png)

    // hay un espacio en blanco a la derecha del atributo 'alt'
    // -> la imagen se alinea a la derecha
    ![Imagen de prueba ](figura1.png)

    // hay espacios en blanco a la izquierda y la derecha del atributo 'alt'
    // -> la imagen se centra
    ![ Imagen de prueba ](figura1.png)

Si el texto del atributo `alt` está encerrado entre comillas, asegúrate de
añadir los espacios en blanco fuera de esas comillas. En el siguiente ejemplo
ninguna imagen define la opción de alineación:

    [code]
    !["Imagen de prueba"](figura1.png)

    ![" Imagen de prueba"](figura1.png)

    !["Imagen de prueba "](figura1.png)

    ![" Imagen de prueba "](figura1.png)

También puedes alinear imágenes cuando utilices la sintaxis alternativa de las
imágenes:

    [code]
    ![Imagen de prueba][1]

    ![ Imagen de prueba][1]

    ![Imagen de prueba ][1]

    ![ Imagen de prueba ][1]

    [1]: figura1.png
