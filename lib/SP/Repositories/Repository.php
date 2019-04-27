<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Context\ContextInterface;
use SP\Storage\Database\Database;

/**
 * Class Repository
 *
 * @package SP\Repositories
 */
abstract class Repository
{
    /**
     * @var ContextInterface
     */
    protected $context;
    /**
     * @var Database
     */
    protected $db;

    /**
     * Repository constructor.
     *
     * @param Database         $database
     * @param ContextInterface $session
     */
    final public function __construct(Database $database, ContextInterface $session)
    {
        $this->db = $database;
        $this->context = $session;

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }
}