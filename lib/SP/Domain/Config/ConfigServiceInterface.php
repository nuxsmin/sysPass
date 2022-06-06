<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Domain\Config;


use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ConfigData;
use SP\DataModel\Dto\ConfigRequest;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

/**
 * Class ConfigService
 *
 * @package SP\Domain\Config\Services
 */
interface ConfigServiceInterface
{
    /**
     * @throws NoSuchItemException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function getByParam(string $param, $default = null);

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ConfigData $configData): int;

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function saveBatch(ConfigRequest $configRequest): void;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function save(string $param, $value): bool;

    /**
     * Obtener un array con la configuración almacenada en la BBDD.
     *
     * @return ConfigData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array;

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    public function deleteByParam(string $param): void;
}