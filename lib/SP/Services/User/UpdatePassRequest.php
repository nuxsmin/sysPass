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

namespace SP\Services\User;

/**
 * Class UpdatePassRequest
 *
 * @package SP\Services\User
 */
final class UpdatePassRequest
{
    /**
     * @var string
     */
    private $pass;
    /**
     * @var int
     */
    private $isChangePass = 0;
    /**
     * @var int
     */
    private $isChangedPass = 0;

    /**
     * UpdatePassRequest constructor.
     *
     * @param string $pass
     */
    public function __construct($pass)
    {
        $this->pass = $pass;
    }

    /**
     * @return string
     */
    public function getPass()
    {
        return $this->pass;
    }

    /**
     * @return int
     */
    public function getisChangePass()
    {
        return $this->isChangePass;
    }

    /**
     * @param int $isChangePass
     */
    public function setIsChangePass($isChangePass)
    {
        $this->isChangePass = $isChangePass;
    }

    /**
     * @return int
     */
    public function getisChangedPass()
    {
        return $this->isChangedPass;
    }

    /**
     * @param int $isChangedPass
     */
    public function setIsChangedPass($isChangedPass)
    {
        $this->isChangedPass = $isChangedPass;
    }
}