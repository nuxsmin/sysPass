<?php

namespace SP\Services;

use SP\Config\Config;
use SP\Core\Events\EventDispatcher;
use SP\Core\Session\Session;
use SP\Core\Traits\InjectableTrait;
use SP\Storage\Database;
use SP\Storage\DatabaseInterface;

/**
 * Class Service
 *
 * @package Services
 */
abstract class Service
{
    use InjectableTrait;

    /** @var Config */
    protected $config;
    /** @var Session */
    protected $session;
    /** @var EventDispatcher */
    protected $eventDispatcher;
    /** @var DatabaseInterface */
    protected $db;

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
     * @param Config          $config
     * @param Session         $session
     * @param EventDispatcher $eventDispatcher
     * @param Database        $db
     */
    public function inject(Config $config, Session $session, EventDispatcher $eventDispatcher, Database $db)
    {
        $this->config = $config;
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;
        $this->db = $db;
    }
}