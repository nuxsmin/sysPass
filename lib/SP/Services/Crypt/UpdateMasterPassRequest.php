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

namespace SP\Services\Crypt;

use SP\Core\Crypt\Hash;
use SP\Services\Task\Task;


/**
 * Class UpdateMasterPassRequest
 *
 * @package SP\Services\Crypt
 */
final class UpdateMasterPassRequest
{
    /**
     * @var string
     */
    private $currentMasterPass;
    /**
     * @var string
     */
    private $newMasterPass;
    /**
     * @var Task
     */
    private $task;
    /**
     * @var string
     */
    private $hash;
    /**
     * @var string
     */
    private $currentHash;

    /**
     * UpdateMasterPassRequest constructor.
     *
     * @param string $currentMasterPass
     * @param string $newMasterPass
     * @param string $currentHash
     * @param Task   $task
     */
    public function __construct($currentMasterPass, $newMasterPass, $currentHash, Task $task = null)
    {
        $this->currentMasterPass = $currentMasterPass;
        $this->newMasterPass = $newMasterPass;
        $this->task = $task;
        $this->hash = Hash::hashKey($newMasterPass);
        $this->currentHash = $currentHash;
    }

    /**
     * @return string
     */
    public function getCurrentMasterPass()
    {
        return $this->currentMasterPass;
    }

    /**
     * @return string
     */
    public function getNewMasterPass()
    {
        return $this->newMasterPass;
    }

    /**
     * @return Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @return bool
     */
    public function useTask()
    {
        return $this->task !== null;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function getCurrentHash()
    {
        return $this->currentHash;
    }

}