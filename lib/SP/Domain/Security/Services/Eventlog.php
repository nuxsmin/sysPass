<?php
declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Security\Models\Eventlog as EventlogModel;
use SP\Domain\Security\Ports\EventlogRepository;
use SP\Domain\Security\Ports\EventlogService;
use SP\Infrastructure\Database\QueryResult;

/**
 * Class Eventlog
 */
final class Eventlog extends Service implements EventlogService
{

    public function __construct(
        Application                         $application,
        private readonly EventlogRepository $eventLogRepository,
        private readonly RequestService $request
    ) {
        parent::__construct($application);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
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
    public function create(EventlogModel $eventlog): int
    {
        $userData = $this->context->getUserData();

        $data = [
            'userId' => $userData->getId(),
            'login' => $userData->getLogin() ?: '-',
            'ipAddress' => $this->request->getClientAddress()
        ];

        return $this->eventLogRepository->create($eventlog->mutate($data))->getLastId();
    }
}
