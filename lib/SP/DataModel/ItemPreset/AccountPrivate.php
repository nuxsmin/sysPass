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

namespace SP\DataModel\ItemPreset;

/**
 * Class AccountPrivate
 *
 * @package SP\DataModel
 */
class AccountPrivate
{
    /**
     * @var bool
     */
    private $privateUser = false;
    /**
     * @var bool
     */
    private $privateGroup = false;

    /**
     * @return bool
     */
    public function isPrivateUser(): bool
    {
        return $this->privateUser;
    }

    /**
     * @param bool $privateUser
     */
    public function setPrivateUser(bool $privateUser)
    {
        $this->privateUser = $privateUser;
    }

    /**
     * @return bool
     */
    public function isPrivateGroup(): bool
    {
        return $this->privateGroup;
    }

    /**
     * @param bool $privateGroup
     */
    public function setPrivateGroup(bool $privateGroup)
    {
        $this->privateGroup = $privateGroup;
    }
}