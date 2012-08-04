<?php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Easybook\Events\EasybookEvents as Events;
use Easybook\Events\ParseEvent;

/**
 * This plugin takes care of the interactive glossary feature.
 * 
 * It will use any html definition list tag (<dl>) as a source for
 * interactive definitions, replacing each term found in the text with
 * a link with its definition in the title attribute, which in turn
 * will be converted by the javascript part into an interactive tooltip. 
 */
class InteractiveGlossaryPlugin implements EventSubscriberInterface
{
    static public function getSubscribedEvents()
    {
        return array(
                Events::POST_PARSE => 'onItemPostParse',);
    }

    public function onItemPostParse(ParseEvent $event)
    {
        $item = $event->getItem();

        // extract all the glossary's definitions
        $definitions = $this->extractGlossaryDefinitions($item['content']);

        // explode pluralized terms
        $definitions = $this->explodePluralizedTerms($definitions);

        // replace each term with a link
        $item['content'] = $this->replaceTerms($item['content'], $definitions);

        $event->setItem($item);
    }

    /**
     * Extract all the glossary's definitions
     * 
     * @param string $item
     * @return array of term => definition 
     */
    protected function extractGlossaryDefinitions($item)
    {
        $definitions = array();

        $regExp = '/';
        $regExp .= '<div class="glossary">(.*)<\/div>'; // capture everything in div
        $regExp .= '/Ums'; // Ungreedy, multiline, dotall

        if (preg_match_all($regExp, $item, $glossaries)) {
            foreach ($glossaries as $glossary) {
                $regExp = '/';
                $regExp .= '<dt>(?<dt>.*)<\/dt>'; // capture definition term
                $regExp .= '.*'; // in-between
                $regExp .= '<dd>(?<dd>.*)<\/dd>'; // capture definition description
                $regExp .= '/Ums'; // Ungreedy, multiline, dotall
                if (preg_match_all($regExp, $glossary[0], $matches)) {
                    foreach ($matches['dt'] as $key => $dt) {
                        $definitions[$dt] = $matches['dd'][$key];
                    }
                }
            }
        }

        return $definitions;
    }

    /**
     * Explode pluralized terms
     * 
     * @param array $definitions
     * @return array $definitions modified accordingly
     */
    protected function explodePluralizedTerms(array $definitions)
    {
        $newDefs = array();

        $regExp = '/';
        $regExp .= '(?<root>[\w\s]*)'; // root of the term (can contain in-between spaces)
        $regExp .= '(\['; // opening square bracket
        $regExp .= '(?<suffixes>.+)'; // suffixes
        $regExp .= '\])?'; // closing square bracket
        $regExp .= '/u'; // unicode

        foreach ($definitions as $term => $description) {
            if (preg_match($regExp, $term, $parts)) {
                if (array_key_exists('suffixes', $parts)) {
                    $suffixes = explode('|', $parts['suffixes']);
                    if (1 == count($suffixes)) {
                        // exactly one suffix means root without and with suffix (i.e. 'word[s]')
                        $newDefs[$parts['root']] = $description;
                        $newDefs[$parts['root'] . $suffixes[0]] = $description;
                    } else {
                        // more than one suffix means all the variations (i.e. 'entit[y|ies]') 
                        foreach ($suffixes as $suffix) {
                            $newDefs[$parts['root'] . $suffix] = $description;
                        }
                    }
                } else {
                    // no suffixes, just the root definition
                    $newDefs[$parts['root']] = $description;
                }

            }
        }

        return $newDefs;
    }

    /**
     * Replace terms in item
     * 
     * @param string $item
     * @param array $definitions
     * @return string The modified item 
     */
    protected function replaceTerms($item, array $definitions)
    {
        /* To avoid problems when the term appears also inside the description,
         * we made the replacement in 4 steps:
         * 1.- Save all the existing src, title and alt attributes (i.e. in images).
         * 2.- Replace all terms found in item with an <a> tag with a placeholder inside the title attribute.
         * 3.- Replace back all previously saved placeholders with the saved value. 
         * 4.- Replace all newly-assigned link title placeholders with the corresponding definition.
         */ 

        // save existing values of attributes before modifying anything 
        $list = $this->saveAttributes(array(
                     'title',
                     'alt',
                     'src'), $item);

        // replace all the defined terms with a link with title="description"
        $listOfDescriptions = array();
        foreach ($definitions as $term => $description) {

            // save the placeholder for this description
            $placeHolder = '#' . count($listOfDescriptions) . '#';
            $listOfDescriptions[$placeHolder] = $description;

            // regexp to replace into. 
            // note that the tag is the first capture group.
            $patterns = array(
                    '/<(p)>(.*)<\/p>/Ums',
                    '/<(li)>(.*)<\/li>/Ums');

            $item = preg_replace_callback($patterns,
                                          function ($matches) use ($term, $description,
                                          $placeHolder)
                {
                    // extract what to replace
                    $tag = $matches[1];
                    $tagContent = $matches[2];

                    // construct the regexp to replace inside the tag content
                    $regExp = '/';
                    $regExp .= '(^|\W)'; // previous delimiter or start of string
                    $regExp .= '(' . $term . ')'; // the term to replace
                    $regExp .= '(\W|$)'; // following delimiter or end of string
                    $regExp .= '/ui'; // unicode, case-insensitive

                    // do the replacement of terms inside the tag contents
                    $repl = sprintf('<a href="#" class="tooltip" title="%s">$2</a>', $placeHolder);
                    $par = preg_replace($regExp, '$1' . $repl . '$3', $tagContent);

                    // reconstruct the original tag
                    return sprintf('<%s>%s</%s>', $tag, $par, $tag);
                }, $item);
        }

        // replace back each ocurrence of the saved placeholders with the corresponding value
        $item = $this->restoreFromList($list, $item);

        // replace each ocurrence of the description placeholders with the corresponding description
        $item = $this->restoreFromList($listOfDescriptions, $item);

        return $item;
    }

    /**
     * Replace the contents of the atributes in item by a placeholder.  
     * 
     * @param array $attribute The attributes to save
     * @param string &$item The item text to modify 
     * @return array $list of placeholders and original values
     */
    protected function saveAttributes(array $attribute, &$item)
    {
        $list = array();

        // replace all the contents of the attribute with a placeholder
        $regex = sprintf('/(%s)="(.*)"/Ums', implode('|', $attribute));

        $item = preg_replace_callback($regex,
                                      function ($matches) use (&$list, $attribute)
            {
                $attr = $matches[1];
                $value = $matches[2];

                $placeHolder = '@' . $attr . count($list) . '@';
                $list[$placeHolder] = $value;
                return sprintf('%s="%s"', $attr, $placeHolder);
            }, $item);

        return $list;
    }

    /**
     * Restore all attributes from the list of placeholders into item
     * 
     * @param array $list of placeholders and its values
     * @param string $item to replace into
     * @return string $item with replacements
     */
    protected function restoreFromList(array $list, $item)
    {
        foreach ($list as $key => $value) {
            $item = preg_replace('/' . $key . '/Ums', $value, $item);
        }

        return $item;
    }
}

