<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Repositories;

use SP\Config\Config;
use SP\Core\Events\EventDispatcher;
use SP\Core\Session\Session;
use SP\Core\Traits\InjectableTrait;
use SP\Storage\Database;
use SP\Storage\DatabaseInterface;

/**
 * Class Repository
 *
 * @package SP\Repositories
 */
abstract class Repository
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