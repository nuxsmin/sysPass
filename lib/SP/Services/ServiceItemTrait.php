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

namespace SP\Services;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Bootstrap;
use SP\DataModel\DataModelInterface;

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
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
}