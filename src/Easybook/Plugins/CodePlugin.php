<?php

/*
 * This file is part of the easybook application.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Easybook\Plugins;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;

class CodePlugin implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_PARSE  => array('onItemPreParse', -500),
            Events::POST_PARSE => array('onItemPostParse', -500),
        );
    }

    public function onItemPreParse(ParseEvent $event)
    {
        $item = $event->getItem();
        // regexp copied from PHP-Markdown
        $item['original'] = preg_replace_callback('{
                (?:\n(?<indent>(?:[ ]{4})*)\n|\A\n?)
                (?<code>                    # $1 = the code block -- one or more lines, starting with a space/tab
                    (?:(?>
                        ^\g{indent}[ ]{4}   # Lines must start with a tab or a tab-width of spaces
                        .*\n
                    ))*
                    (?:(?>
                        ^\g{indent}[ ]{4}   # Lines must start with a tab or a tab-width of spaces
                        .*
                    ))
                )
            }xm',
            function ($matches) use ($event) {
                $code = $matches['code'];
                $indent = $matches['indent'];

                // outdent codeblock
                $code = preg_replace('/^(' . $indent . '[ ]{4})/m', '', $code);

                // if present, strip code language declaration ([php], [js], ...)
                $language = 'code';
                $code = preg_replace_callback('{
                        ^\[(?<lang>.*)\]\n(?<code>.*)
                    }x',
                    function ($matches) use (&$language) {
                        $language = trim($matches['lang']);

                        return $matches['code'];
                    },
                    $code
                );

                // highlight code if the edition wants to
                if ($event->app->edition('highlight_code')) {
                    $code = $event->app->highlight($code, $language);
                } // escape code to show it instead of interpreting it
                else {
                    // yaml-style comments could be interpreted as Markdown headings
                    // replace any starting # character by its HTML entity (&#35;)
                    $code = '<pre>'
                        . preg_replace('/^# (.*)/', "&#35; $1", htmlspecialchars($code))
                        . '</pre>';
                }

                // the publishing edition wants to label codeblocks
                // TODO

                $code = $event->app->render('code.twig', array(
                    'item' => array(
                        'content'  => $code,
                        'language' => $language,
                        'number'   => '',
                        'slug'     => ''
                    )
                ));

                // indent code block
                return "\n$indent\n$indent" . str_replace("\n", "\n" . $indent, $code);

            },
            $item['original']
        );
        $event->setItem($item);
    }

    public function onItemPostParse(ParseEvent $event)
    {
        $item = $event->getItem();

        // unescape yaml-style comments that before parsing could
        // be interpreted as Markdown first-level headings
        $item['content'] = str_replace('&#35;', '#', $item['content']);

        $event->setItem($item);
    }
}
