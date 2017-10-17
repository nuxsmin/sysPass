<?php

namespace Services;

use SP\Config\Config;
use SP\Core\Events\EventDispatcher;
use SP\Core\Session\Session;
use SP\Core\Traits\InjectableTrait;

/**
 * Class Service
 * @package Services
 */
abstract class Service
{
    use InjectableTrait;
    /** @var  Config */
    protected $config;
    /** @var  Session */
    protected $session;
    /** @var  EventDispatcher */
    protected $eventDispatcher;

    /**
     * Service constructor.
     */
    final public function __construct()
    {
        $this->injectDependencies();

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }

    /**
     * @param Config $config
     * @param Session $session
     * @param EventDispatcher $eventDispatcher
     */
    public function inject(Config $config, Session $session, EventDispatcher $eventDispatcher)
    {
        $this->config = $config;
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;
    }
}