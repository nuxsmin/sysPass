<?php

namespace SP\Core\Plugin;

use SplObserver;

abstract class PluginBase implements SplObserver
{
    /**
     * @var string Tipo de plugin
     */
    protected $type;

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}