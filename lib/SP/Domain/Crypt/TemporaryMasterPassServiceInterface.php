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

namespace SP\Domain\Crypt;


use Defuse\Crypto\Exception\CryptoException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

/**
 * Class TemporaryMasterPassService
 *
 * @package SP\Domain\Crypt\Services
 */
interface TemporaryMasterPassServiceInterface
{
    /**
     * Crea una clave temporal para encriptar la clave maestra y guardarla.
     *
     * @param  int  $maxTime  El tiempo máximo de validez de la clave
     *
     * @return string
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function create(int $maxTime = 14400): string;

    /**
     * Comprueba si la clave temporal es válida
     *
     * @param  string  $pass  clave a comprobar
     *
     * @return bool
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function checkTempMasterPass(string $pass): bool;

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function sendByEmailForGroup(int $groupId, string $key): void;

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function sendByEmailForAllUsers(string $key): void;

    /**
     * Devuelve la clave maestra que ha sido encriptada con la clave temporal
     *
     * @param $key string con la clave utilizada para encriptar
     *
     * @return string con la clave maestra desencriptada
     * @throws NoSuchItemException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws CryptoException
     */
    public function getUsingKey(string $key): string;
}