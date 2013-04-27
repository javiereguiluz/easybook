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

use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;


/**
 * Correct the footnotes markup to match PrinceXML format.
 *
 * @author Oscar Cubo Medina <ocubom@gmail.com>
 */
class FixPrinceFootnotesPlugin implements EventSubscriberInterface
{
    /**
     * {{ @inheritdoc }}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::POST_PARSE => array('onItemPostParse', 0),
        );
    }

    /**
     * Change footnote markup to match the PrinceXML format.
     *
     * @param  ParseEvent $event easybook event
     */
    public function onItemPostParse(ParseEvent $event)
    {
        // only makes sense when used with PDF publisher
        if (!$event->app['publisher'] instanceof \Easybook\Publishers\PdfPublisher) {
            return;
        }

        $item = $event->getItem();

        // obtain  all footnotes
        $footnotes = $this->searchFootnotes($item['content']);
        if (null === $footnotes) {
            return; // no footnote found => do nothing
        }

        // delete footnotes block (must be the latest content)
        $item['content'] = substr(
            $item['content'],
            0,
            strrpos($item['content'], '<div class="footnotes">')
        );

        // replace footnote calls with its text
        $item['content'] = str_replace(

            array_map(
                function ($footnote) {
                    return $footnote['call'];
                },
                $footnotes
            ),

            array_map(
                function ($footnote) {
                    return sprintf('<span class="fn">%s</span>', $footnote['text']);
                },
                $footnotes
            ),

            $item['content']
        );

        // replace item
        $event->setItem($item);
    }

    /**
     * Search footnote markup (both call and note)
     *
     * @param  string $html HTML markup
     *
     * @return array
     */
    private function searchFootnotes($html)
    {
        $footnotes = array();

        $crawler = new Crawler($html);

        //
        // footnote text:
        //
        // <li id="fn:1">
        //     <p>[text]&#160;<a href="#fnref:1" rev="footnote">&#8617;</a></p>
        // </li>
        //
        $crawler->filter('a[rev="footnote"]')->each(
            function ($link) use (&$footnotes) {

                // use href (internal link) as identifier
                $id = substr($link->getAttribute('href'), 1);

                // Obtain the <li> element
                $footnote = $link->parentNode->parentNode;

                // pack all paragraphs text
                $html = '';
                foreach($footnote->getElementsByTagName('p') as $text) {
                    $html .= $text->c14n();
                }

                // delete backref link on text
                $footnotes[$id]['text'] = str_replace($link->c14n(), '', $html);

            }
        );

        //
        // footnote call:
        //
        // <sup id="fnref:1"><a href="#fn:1" rel="footnote">1</a></sup>
        //
        $crawler->filter('a[rel="footnote"]')->each(
            function ($link) use (&$footnotes) {

                // obtain the <sup> element
                $call = $link->parentNode;

                // use internal id
                $id = $call->getAttribute('id');

                // pack footnote call
                $footnotes[$id]['call'] = $call->c14n();

            }
        );

        return count($footnotes) > 0 ? $footnotes : null;
    }
}
