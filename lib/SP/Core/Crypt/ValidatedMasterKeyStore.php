<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

declare(strict_types=1);

namespace SP\Core\Crypt;

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Crypt\Ports\MasterKeyStore;
use SP\Domain\Crypt\Ports\MasterPasswordRequestLock;

final class ValidatedMasterKeyStore implements MasterKeyStore
{
    public function __construct(
        private readonly MasterKeyStore $inner,
        private readonly MasterPasswordRequestLock $requestLock,
        private readonly ConfigDataInterface $config
    ) {
    }

    public function get(): string
    {
        $key = $this->inner->get();
        if ($this->config->isInstalled()) {
            $this->requestLock->assertCurrentMasterPassword($key);
        }
        return $key;
    }

    public function set(string $masterKey): void
    {
        if ($this->config->isInstalled()) {
            $this->requestLock->assertCurrentMasterPassword($masterKey);
        }
        $this->inner->set($masterKey);
    }

    public function clear(): void
    {
        $this->inner->clear();
    }
}
