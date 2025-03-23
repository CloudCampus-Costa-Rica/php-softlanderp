<?php

namespace SoftlandERP;

class Config
{

    /**
     * 
     */
    private $options = [];

    /**
     * Initializes a new instance of Config.
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Gets the Config as an array.
     */
    public function toArray()
    {
        return $this->options;
    }

    public function get($name, $default = null)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }
        return $default;
    }
}