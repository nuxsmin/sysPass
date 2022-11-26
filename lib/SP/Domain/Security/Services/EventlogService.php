<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Security\Services;

use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\EventlogData;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Security\Ports\EventlogRepositoryInterface;
use SP\Domain\Security\Ports\EventlogServiceInterface;
use SP\Http\RequestInterface;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\Security\Repositories\EventlogRepository;

/**
 * Class EventlogService
 *
 * @package SP\Domain\Common\Services\EventLog
 */
final class EventlogService extends Service implements EventlogServiceInterface
{
    protected EventlogRepository $eventLogRepository;
    protected RequestInterface   $request;

    public function __construct(
        Application $application,
        EventlogRepositoryInterface $eventLogRepository,
        RequestInterface $request
    ) {
        parent::__construct($application);

        $this->eventLogRepository = $eventLogRepository;
        $this->request = $request;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->eventLogRepository->search($itemSearchData);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function clear(): bool
    {
        return $this->eventLogRepository->clear();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(EventlogData $eventlogData): int
    {
        $userData = $this->context->getUserData();

        $eventlogData->setUserId($userData->getId());
        $eventlogData->setLogin($userData->getLogin() ?: '-');
        $eventlogData->setIpAddress($this->request->getClientAddress());

        return $this->eventLogRepository->create($eventlogData);
    }
}
