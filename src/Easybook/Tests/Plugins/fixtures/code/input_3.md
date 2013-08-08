This code block should be properly parsed and highlighted:

```php
class Finder implements \IteratorAggregate, \Countable
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->ignore = static::IGNORE_VCS_FILES | static::IGNORE_DOT_FILES;
        $this
            ->addAdapter(new GnuFindAdapter())
            ->addAdapter(new BsdFindAdapter())
            ->addAdapter(new PhpAdapter(), -50)
            ->setAdapter('php')
        ;
    }
    // ...
}
```

This is an expanded version of the previous code block and it should be also
properly parsed and highlighted, because fenced code blocks don't suffer the
shortcomings of the default code block type:

```php
<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Finder;

use Symfony\Component\Finder\Adapter\AdapterInterface;
use Symfony\Component\Finder\Adapter\GnuFindAdapter;
use Symfony\Component\Finder\Adapter\BsdFindAdapter;
use Symfony\Component\Finder\Adapter\PhpAdapter;
use Symfony\Component\Finder\Exception\ExceptionInterface;

class Finder implements \IteratorAggregate, \Countable
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->ignore = static::IGNORE_VCS_FILES | static::IGNORE_DOT_FILES;

        $this
            ->addAdapter(new GnuFindAdapter())
            ->addAdapter(new BsdFindAdapter())
            ->addAdapter(new PhpAdapter(), -50)
            ->setAdapter('php')
        ;
    }

    // ...
}
```

This is a code block with a generic code listing because it specifies no particular
programming language:

```
// Bubble sort pseudocode implementation
// see http://en.wikipedia.org/wiki/Bubble_sort#Pseudocode_implementation
procedure bubbleSort( A : list of sortable items )
   repeat
     swapped = false
     for i = 1 to length(A) - 1 inclusive do:
       /* if this pair is out of order */
       if A[i-1] > A[i] then
         /* swap them and remember something changed */
         swap( A[i-1], A[i] )
         swapped = true
       end if
     end for
   until not swapped
end procedure
```

This code block uses the default block style. Therefore, it shouldn't be
parsed and it should be displayed as is:

    [twig]
    {% for row in items|batch(3) %}
        <div class=row>
            {% for column in row %}
                <div class=item>{{ column }}</div>
            {% endfor %}
        </div>
    {% endfor %}

And the following code listing uses the fenced-style code block, so it should
also be output as is, without parsing or highlighting:

~~~ .twig
{% for row in items|batch(3) %}
    <div class=row>
        {% for column in row %}
            <div class=item>{{ column }}</div>
        {% endfor %}
    </div>
{% endfor %}
~~~

The last code is a bit special, because it contains the special code block tag
that, in this case, should not be interpreted, but displayed:

```
    ```php
    public function start($callback = null)
    {
        if (null === $this->getCommandLine()) {
            if (false === $php = $this->executableFinder->find()) {
                throw new RuntimeException('Unable to find the PHP executable.');
            }
            $this->setCommandLine($php);
        }
        parent::start($callback);
    }
    ```
```
