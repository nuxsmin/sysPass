<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Account;

use SP\Core\Exceptions\SPException;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\AccountHistoryData;
use SP\Repositories\Account\AccountHistoryRepository;

/**
 * Class AccountHistoryService
 *
 * @package SP\Services\Account
 */
class AccountHistoryService
{
    use InjectableTrait;

    /**
     * @var AccountHistoryRepository
     */
    protected $accountHistoryRepository;

    /**
     * AccountHistoryService constructor.
     *
     * @throws \SP\Core\Dic\ContainerException
     */
    public function __construct()
    {
        $this->injectDependencies();
    }

    /**
     * @param AccountHistoryRepository $accountHistoryRepository
     */
    public function inject(AccountHistoryRepository $accountHistoryRepository)
    {
        $this->accountHistoryRepository = $accountHistoryRepository;
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return AccountHistoryData
     * @throws SPException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getById($id)
    {
        return $this->accountHistoryRepository->getById($id);
    }

}