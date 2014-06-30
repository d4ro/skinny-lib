<?php

namespace Skinny\Cache;

/**
 * Description of CacheBase
 *
 * @author Daro
 */
abstract class CacheBase {

    protected $_options;
    protected $_storage;
    protected $_next;

    public function __construct($options = array()) {
        ;
    }

    public function __get($name) {
        return $this->getValue($name);
    }

    public function get($name, $callback = null) {
        if ($this->has($name))
            return $this->getValue($name);

        if (null === $callback)
            return null;

        $value = null;
        $tags = null;

        if (true !== call_user_func($callback, $this, $name, $value, $tags))
            throw new CacheException('Callback function to get an item from cache has failed.');

        $this->set($name, $value, $tags);

        return $value;
    }

    public abstract function getValue($name);

    public function __set($name, $value) {
        return $this->set($name, $value);
    }

    public abstract function set($name, $value, $tags = null);

    public abstract function setTags($name, $tags);

    public abstract function addTags($name, $tags);

    public abstract function removeTags($name, $tags, $tagsOptions);

    public abstract function has($name);

    public abstract function find($tags, $tagsOptions);

    public abstract function remove($name);

    public abstract function removeByTags($tags, $tagsOptions);
}
