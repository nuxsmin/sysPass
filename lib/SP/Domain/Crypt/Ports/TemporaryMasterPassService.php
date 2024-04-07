<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Crypt\Ports;

use PHPMailer\PHPMailer\Exception;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

/**
 * Class TemporaryMasterPassService
 *
 * @package SP\Domain\Crypt\Services
 */
interface TemporaryMasterPassService
{
    /**
     * Crea una clave temporal para encriptar la clave maestra y guardarla.
     *
     * @param  int  $maxTime  El tiempo máximo de validez de la clave
     *
     * @return string
     * @throws ServiceException
     */
    public function create(int $maxTime = 14400): string;

    /**
     * Comprueba si la clave temporal es válida
     *
     * @param  string  $pass  clave a comprobar
     *
     * @return bool
     * @throws ServiceException
     */
    public function checkTempMasterPass(string $pass): bool;

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     * @throws Exception
     */
    public function sendByEmailForGroup(int $groupId, string $key): void;

    /**
     * @throws Exception
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     */
    public function sendByEmailForAllUsers(string $key): void;

    /**
     * Devuelve la clave maestra que ha sido encriptada con la clave temporal
     *
     * @param $key string con la clave utilizada para encriptar
     *
     * @return string con la clave maestra desencriptada
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws CryptException
     */
    public function getUsingKey(string $key): string;
}
