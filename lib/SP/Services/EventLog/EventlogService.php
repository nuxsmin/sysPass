<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Services\EventLog;

use SP\DataModel\EventlogData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\EventLog\EventlogRepository;
use SP\Services\Service;
use SP\Util\HttpUtil;

/**
 * Class EventlogService
 *
 * @package SP\Services\EventLog
 */
class EventlogService extends Service
{
    /**
     * @var EventlogRepository
     */
    protected $eventLogRepository;

    /**
     * @param ItemSearchData $itemSearchData
     * @return mixed
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->eventLogRepository->search($itemSearchData);
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function clear()
    {
        return $this->eventLogRepository->clear();
    }

    /**
     * @param EventlogData $eventlogData
     * @return int
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create(EventlogData $eventlogData)
    {
        $userData = $this->context->getUserData();

        $eventlogData->setUserId($userData->getId());
        $eventlogData->setLogin($userData->getLogin() ?: '-');
        $eventlogData->setIpAddress(HttpUtil::getClientAddress());

        return $this->eventLogRepository->create($eventlogData);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->eventLogRepository = $this->dic->get(EventlogRepository::class);
    }
}