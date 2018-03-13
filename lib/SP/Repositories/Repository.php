<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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
use SP\Core\Context\ContextInterface;
use SP\Core\Dic\Container;
use SP\Core\Events\EventDispatcher;
use SP\Storage\Database;
use SP\Storage\DatabaseInterface;

/**
 * Class Repository
 *
 * @package SP\Repositories
 */
abstract class Repository
{
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var ContextInterface
     */
    protected $context;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var DatabaseInterface
     */
    protected $db;
    /**
     * @var Container
     */
    private $dic;

    /**
     * Repository constructor.
     *
     * @param Container        $dic
     * @param Config           $config
     * @param Database         $database
     * @param ContextInterface $session
     * @param EventDispatcher  $eventDispatcher
     */
    final public function __construct(Container $dic, Config $config, Database $database, ContextInterface $session, EventDispatcher $eventDispatcher)
    {
        $this->dic = $dic;
        $this->config = $config;
        $this->db = $database;
        $this->context = $session;
        $this->eventDispatcher = $eventDispatcher;

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }
}