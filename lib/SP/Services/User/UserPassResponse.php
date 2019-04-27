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
 * Class UserPassResponse
 *
 * @package SP\DataModel\Dto
 */
final class UserPassResponse
{
    /**
     * @var int
     */
    private $status;
    /**
     * @var string
     */
    private $cryptMasterPass;
    /**
     * @var string
     */
    private $cryptSecuredKey;
    /**
     * @var string
     */
    private $clearMasterPass;

    /**
     * UserPassResponse constructor.
     *
     * @param int    $status
     * @param string $clearUserMPass
     */
    public function __construct($status, $clearUserMPass = null)
    {
        $this->status = $status;
        $this->clearMasterPass = $clearUserMPass;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getCryptMasterPass()
    {
        return $this->cryptMasterPass;
    }

    /**
     * @param string $cryptMasterPass
     */
    public function setCryptMasterPass($cryptMasterPass)
    {
        $this->cryptMasterPass = $cryptMasterPass;
    }

    /**
     * @return string
     */
    public function getCryptSecuredKey()
    {
        return $this->cryptSecuredKey;
    }

    /**
     * @param string $cryptSecuredKey
     */
    public function setCryptSecuredKey($cryptSecuredKey)
    {
        $this->cryptSecuredKey = $cryptSecuredKey;
    }

    /**
     * @return string
     */
    public function getClearMasterPass()
    {
        return $this->clearMasterPass;
    }

    /**
     * @param string $clearMasterPass
     */
    public function setClearMasterPass($clearMasterPass)
    {
        $this->clearMasterPass = $clearMasterPass;
    }
}