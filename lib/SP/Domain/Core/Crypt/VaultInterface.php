<?php
declare(strict_types=1);
/**
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

namespace SP\Domain\Core\Crypt;

use SP\Domain\Core\Exceptions\CryptException;

/**
 * Class VaultInterface
 */
interface VaultInterface
{
    /**
     * Re-key this vault
     *
     * @throws CryptException
     */
    public function reKey(string $newSeed, string $oldSeed): VaultInterface;

    /**
     * Create a new vault with the saved data
     *
     * @throws CryptException
     */
    public function saveData(string $data, string $key): VaultInterface;

    /**
     * Get the data decrypted
     *
     * @throws CryptException
     */
    public function getData(string $key): string;

    /**
     * Serialize the current vault
     */
    public function getSerialized(): string;

    /**
     * Get the last time the key and data were set
     *
     * @return int
     */
    public function getTimeSet(): int;
}
