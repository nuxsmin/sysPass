<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Crypt;

use Defuse\Crypto\Exception\CryptoException;
use RuntimeException;

/**
 * Class Vault
 *
 * @package SP\Core\Crypt
 */
final class Vault
{
    private ?string $data = null;
    private ?string $key = null;
    private int $timeSet = 0;
    private int $timeUpdated = 0;

    public static function getInstance(): Vault
    {
        return new self();
    }

    /**
     * Regenerar la clave de sesión
     *
     * @throws CryptoException
     */
    public function reKey(string $newSeed, string $oldSeed): Vault
    {
        $this->timeUpdated = time();
        $sessionMPass = $this->getData($oldSeed);

        $this->saveData($sessionMPass, $newSeed);

        return $this;
    }

    /**
     * Devolver la clave maestra de la sesión
     *
     * @throws CryptoException
     */
    public function getData(string $key): string
    {
        if ($this->data === null || $this->key === null) {
            throw new RuntimeException('Either data or key must be set');
        }

        return Crypt::decrypt($this->data, $this->key, $key);
    }

    /**
     * Guardar la clave maestra en la sesión
     *
     * @throws CryptoException
     */
    public function saveData($data, string $key): Vault
    {
        if ($this->timeSet === 0) {
            $this->timeSet = time();
        }

        $this->key = Crypt::makeSecuredKey($key);
        $this->data = Crypt::encrypt($data, $this->key, $key);

        return $this;
    }

    public function getTimeSet(): int
    {
        return $this->timeSet;
    }

    public function getTimeUpdated(): int
    {
        return $this->timeUpdated;
    }

    /**
     * Serializaes the current object
     */
    public function getSerialized(): string
    {
        return serialize($this);
    }
}