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

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\TrackData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Security\In\TrackRepositoryInterface;
use SP\Domain\Security\TrackServiceInterface;
use SP\Http\RequestInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\Security\Repositories\TrackRepository;
use SP\Infrastructure\Security\Repositories\TrackRequest;

/**
 * Class TrackService
 *
 * @package SP\Domain\Common\Services
 */
final class TrackService extends Service implements TrackServiceInterface
{
    /**
     * Tiempo para contador de intentos
     */
    public const TIME_TRACKING              = 600;
    public const TIME_TRACKING_MAX_ATTEMPTS = 10;
    public const TIME_SLEEP                 = 0.5;

    private TrackRepository  $trackRepository;
    private RequestInterface $request;

    public function __construct(Application $application, TrackRepositoryInterface $trackRepository, RequestInterface $request)
    {
        parent::__construct($application);

        $this->trackRepository = $trackRepository;
        $this->request = $request;
    }


    /**
     * @throws InvalidArgumentException
     */
    public function getTrackRequest(string $source): TrackRequest
    {
        $trackRequest = new TrackRequest(time() - self::TIME_TRACKING, $source);
        $trackRequest->setTrackIp($this->request->getClientAddress());

        return $trackRequest;
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function delete(int $id): void
    {
        if ($this->trackRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Track not found'));
        }
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function unlock(int $id): void
    {
        if ($this->trackRepository->unlock($id) === 0) {
            throw new NoSuchItemException(__u('Track not found'));
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function clear(): bool
    {
        return $this->trackRepository->clear();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById(int $id): TrackData
    {
        $result = $this->trackRepository->getById($id);

        if ($result->getNumRows() === 0) {
            throw new NoSuchItemException(__u('Track not found'));
        }

        return $result->getData();
    }

    /**
     * @return TrackData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array
    {
        return $this->trackRepository->getAll()->getDataAsArray();
    }

    /**
     * Comprobar los intentos de login
     *
     * @return bool True if delay is performed, false otherwise
     * @throws Exception
     */
    public function checkTracking(TrackRequest $trackRequest): bool
    {
        try {
            $attempts = $this->getTracksForClientFromTime($trackRequest);

            if ($attempts >= self::TIME_TRACKING_MAX_ATTEMPTS) {
                $delaySeconds = self::TIME_SLEEP * $attempts;

                $this->eventDispatcher->notifyEvent(
                    'track.delay',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(
                                sprintf(__('Attempts exceeded (%d/%d)'), $attempts, self::TIME_TRACKING_MAX_ATTEMPTS)
                            )
                            ->addDetail(__u('Seconds'), $delaySeconds)
                    )
                );

                logger('Tracking delay: '.$delaySeconds.'s');

                sleep($delaySeconds);

                return true;
            }
        } catch (Exception $e) {
            processException($e);

            throw $e;
        }

        return false;
    }

    /**
     * Devuelve los tracks de un cliente desde un tiempo y origen determinados
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getTracksForClientFromTime(TrackRequest $trackRequest): int
    {
        return $this->trackRepository->getTracksForClientFromTime($trackRequest)->getNumRows();
    }

    /**
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(TrackRequest $trackRequest): int
    {
        if ($trackRequest->getIpv4() === null && $trackRequest->getIpv6() === null) {
            throw new ServiceException(__u('IP address not set'));
        }

        $result = $this->trackRepository->add($trackRequest);

        $this->eventDispatcher->notifyEvent(
            'track.add',
            new Event(
                $this,
                EventMessage::factory()->addDescription($this->request->getClientAddress(true))
            )
        );

        return $result;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData): QueryResult
    {
        return $this->trackRepository->search($itemSearchData);
    }
}