<?php declare(strict_types=1);

namespace Easybook\Events;

/**
 * The object passed to the events related to the parsing of contents.
 * It provides access for the application object, the specific item
 * being published and to any of the item's properties.
 */
final class ParseEvent extends AbstractEvent
{
    /**
     * Getter for any of the properties of the item being published.
     *
     * @param string $key The name of the requested item property
     *
     * @return mixed The value of the requested property
     */
    public function getItemProperty(string $key)
    {
        return $this->app['publishing.active_item'][$key];
    }

    /**
     * Setter for any of the properties of the item being published.
     *
     * @param string $key   The name of the property to modify
     * @param mixed  $value The new value of the property
     */
    public function setItemProperty(string $key, $value): void
    {
        $item = $this->app['publishing.active_item'];
        $item[$key] = $value;

        $this->app['publishing.active_item'] = $item;
    }
}
