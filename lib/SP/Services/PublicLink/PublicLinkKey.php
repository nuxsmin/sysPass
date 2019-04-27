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

namespace SP\Services\PublicLink;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use SP\Util\PasswordUtil;

/**
 * Class PublicLinkKey
 *
 * @package SP\Services\PublicLink
 */
final class PublicLinkKey
{
    /**
     * @var string
     */
    private $hash;
    /**
     * @var string
     */
    private $salt;

    /**
     * PublicLinkKey constructor.
     *
     * @param string $salt
     * @param string $hash
     *
     * @throws EnvironmentIsBrokenException
     */
    public function __construct(string $salt, string $hash = null)
    {
        $this->salt = $salt;

        if ($hash === null) {
            $this->hash = PasswordUtil::generateRandomBytes();
        } else {
            $this->hash = $hash;
        }
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return sha1($this->salt . $this->hash);
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }
}