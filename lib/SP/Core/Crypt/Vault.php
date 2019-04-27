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

namespace SP\Core\Crypt;

use Defuse\Crypto\Exception\CryptoException;

/**
 * Class Vault
 *
 * @package SP\Core\Crypt
 */
final class Vault
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
     * @return static
     */
    public static function getInstance()
    {
        return new static();
    }

    /**
     * Regenerar la clave de sesión
     *
     * @param string $newSeed
     * @param string $oldSeed
     *
     * @return Vault
     * @throws CryptoException
     */
    public function reKey($newSeed, $oldSeed)
    {
        $this->timeUpdated = time();
        $sessionMPass = $this->getData($oldSeed);

        $this->saveData($sessionMPass, $newSeed);

        return $this;
    }

    /**
     * Devolver la clave maestra de la sesión
     *
     * @param string $key
     *
     * @return string
     * @throws CryptoException
     */
    public function getData($key)
    {
        return Crypt::decrypt($this->data, $this->key, $key);
    }

    /**
     * Guardar la clave maestra en la sesión
     *
     * @param mixed  $data
     * @param string $key
     *
     * @return $this
     * @throws CryptoException
     */
    public function saveData($data, $key)
    {
        if ($this->timeSet === 0) {
            $this->timeSet = time();
        }

        $this->key = Crypt::makeSecuredKey($key);
        $this->data = Crypt::encrypt($data, $this->key, $key);

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

    /**
     * Serializaes the current object
     */
    public function getSerialized()
    {
        return serialize($this);
    }
}