<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Crypt;

use SP\Core\Session as CoreSession;
use SP\Core\SessionUtil;

/**
 * Class Vault
 *
 * @package SP\Core\Crypt
 */
class Vault
{
    /**
     * @var string
     */
    private $data;
    /**
     * @var string
     */
    private $key;
    /**
     * @var int
     */
    private $timeSet = 0;
    /**
     * @var int
     */
    private $timeUpdated = 0;

    /**
     * Regenerar la clave de sesión
     *
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function reKey()
    {
        $this->timeUpdated = time();
        $sessionMPass = $this->getData();

        SessionUtil::regenerate();

        $this->saveData($sessionMPass);

        return $this;
    }

    /**
     * Devolver la clave maestra de la sesión
     *
     * @return string
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     */
    public function getData()
    {
        $securedKey = Crypt::unlockSecuredKey($this->key, $this->getKey());

        return Crypt::decrypt($this->data, $securedKey, $this->getKey());
    }

    /**
     * Devolver la clave utilizada para generar la llave segura
     *
     * @return string
     */
    private function getKey()
    {
        return session_id() . CoreSession::getSidStartTime();
    }

    /**
     * Guardar la clave maestra en la sesión
     *
     * @param $data
     * @return $this
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public function saveData($data)
    {
        if ($this->timeSet === 0) {
            $this->timeSet = time();
        }

        $this->key = Crypt::makeSecuredKey($this->getKey());
        $this->data = Crypt::encrypt($data, $this->key, $this->getKey());

        return $this;
    }

    /**
     * @return int
     */
    public function getTimeSet()
    {
        return $this->timeSet;
    }

    /**
     * @return int
     */
    public function getTimeUpdated()
    {
        return $this->timeUpdated;
    }
}