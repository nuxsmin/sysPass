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

use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Crypt\RequestBasedPasswordInterface;
use SP\Http\RequestInterface;

/**
 * Class RequestBasedPassword
 */
final class RequestBasedPassword implements RequestBasedPasswordInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ConfigDataInterface $configData
    ) {}

    public function build(): string
    {
        return hash_pbkdf2(
            'sha1',
            $this->getWellKnownData(),
            $this->configData->getPasswordSalt(),
            5000,
            32
        );
    }

    /**
     * @return string
     */
    private function getWellKnownData(): string
    {
        return sha1(
            $this->request->getHeader('User-Agent').
            $this->request->getClientAddress()
        );
    }
}
