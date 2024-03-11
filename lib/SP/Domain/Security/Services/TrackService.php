<?php
/*
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

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\ItemSearchData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Security\Ports\TrackRepository;
use SP\Domain\Security\Ports\TrackServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
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

    public function __construct(
        Application                       $application,
        private readonly TrackRepository  $trackRepository,
        private readonly RequestInterface $request
    ) {
        parent::__construct($application);
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

                $this->eventDispatcher->notify(
                    'track.delay',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(
                                sprintf(
                                    __('Attempts exceeded (%d/%d)'),
                                    $attempts,
                                    self::TIME_TRACKING_MAX_ATTEMPTS
                                )
                            )
                            ->addDetail(__u('Seconds'), $delaySeconds)
                    )
                );

                logger('Tracking delay: ' . $delaySeconds . 's');

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
    private function getTracksForClientFromTime(TrackRequest $trackRequest): int
    {
        return $this->trackRepository->getTracksForClientFromTime($trackRequest)->getNumRows();
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(TrackRequest $trackRequest): int
    {
        if ($trackRequest->getIpv4() === null && $trackRequest->getIpv6() === null) {
            throw new ServiceException(__u('IP address not set'));
        }

        $result = $this->trackRepository->add($trackRequest);

        $this->eventDispatcher->notify(
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
        return $this->trackRepository->search($itemSearchData, time() - TrackService::TIME_TRACKING);
    }
}
