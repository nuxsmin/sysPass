<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use RuntimeException;
use SP\Core\Exceptions\CryptException;

/**
 * Class Vault
 *
 * @package SP\Core\Crypt
 */
final class Vault
{
    private ?string $data    = null;
    private ?string $key     = null;
    private int     $timeSet = 0;

    private function __construct(private readonly CryptInterface $crypt)
    {
    }

    public static function factory(CryptInterface $crypt): Vault
    {
        return new self($crypt);
    }

    /**
     * Re-key this vault
     *
     * @throws CryptException
     */
    public function reKey(string $newSeed, string $oldSeed): Vault
    {
        return $this->saveData($this->getData($oldSeed), $newSeed);
    }

    /**
     * Create a new vault with the saved data
     *
     * @throws CryptException
     */
    public function saveData($data, string $key): Vault
    {
        $vault = new Vault($this->crypt);
        $vault->timeSet = time();
        $vault->key = $this->crypt->makeSecuredKey($key);
        $vault->data = $this->crypt->encrypt($data, $vault->key, $key);

        return $vault;
    }

    /**
     * Get the data decrypted
     *
     * @throws CryptException
     */
    public function getData(string $key): string
    {
        if ($this->data === null || $this->key === null) {
            throw new RuntimeException('Either data or key must be set');
        }

        return $this->crypt->decrypt($this->data, $this->key, $key);
    }

    /**
     * Serialize the current vault
     */
    public function getSerialized(): string
    {
        return serialize($this);
    }

    /**
     * Get the last time the key and data were set
     *
     * @return int
     */
    public function getTimeSet(): int
    {
        return $this->timeSet;
    }
}
