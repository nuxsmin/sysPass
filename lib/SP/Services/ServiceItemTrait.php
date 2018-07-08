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

namespace SP\Services;

use DI\Container;
use SP\Bootstrap;
use SP\DataModel\DataModelInterface;
use SP\Storage\Database\Database;

/**
 * Trait ServiceItemTrait
 *
 * @package SP\Services
 */
trait ServiceItemTrait
{
    /**
     * Returns service items for a select
     *
     * @return DataModelInterface[]
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function getItemsBasic()
    {
        return Bootstrap::getContainer()->get(static::class)->getAllBasic();
    }

    /**
     * Get all items from the service's repository
     *
     * @return mixed
     */
    abstract public function getAllBasic();

    /**
     * Bubbles a Closure in a database transaction
     *
     * @param \Closure  $closure
     * @param Container $container
     *
     * @return mixed
     * @throws ServiceException
     * @throws \Exception
     */
    private function transactionAware(\Closure $closure, Container $container)
    {
        $database = $container->get(Database::class);

        if ($database->beginTransaction()) {
            try {
                $result = $closure->call($this);

                $database->endTransaction();

                return $result;
            } catch (\Exception $e) {
                $database->rollbackTransaction();

                debugLog('Rollback');

                throw $e;
            }
        } else {
            throw new ServiceException(__u('No es posible iniciar una transacción'));
        }
    }
}