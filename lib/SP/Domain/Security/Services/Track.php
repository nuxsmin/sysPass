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

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Services\Service;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Models\Track as TrackModel;
use SP\Domain\Security\Ports\TrackRepository;
use SP\Domain\Security\Ports\TrackService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;

use function SP\__;
use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * Class Track
 */
final class Track extends Service implements TrackService
{
    private const TIME_TRACKING              = 600;
    private const TIME_TRACKING_MAX_ATTEMPTS = 10;
    private const TIME_SLEEP                 = 0.25;

    public function __construct(
        Application                      $application,
        private readonly TrackRepository $trackRepository,
        private readonly RequestService  $request
    ) {
        parent::__construct($application);
    }


    /**
     * @throws InvalidArgumentException
     */
    public function buildTrackRequest(string $source): TrackRequest
    {
        $time = time() - self::TIME_TRACKING;
        return new TrackRequest($time, $source, $this->request->getClientAddress());
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function unlock(int $id): void
    {
        if ($this->trackRepository->unlock($id) === 0) {
            throw NoSuchItemException::info(__u('Track not found'));
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
            $attempts = $this->trackRepository->getTracksForClientFromTime($this->buildTrackFrom($trackRequest))
                                              ->getNumRows();

            if ($attempts >= self::TIME_TRACKING_MAX_ATTEMPTS) {
                $delaySeconds = (int)(self::TIME_SLEEP * $attempts);

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
                            ->addDetail(__u('Seconds'), (string)$delaySeconds)
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

    private function buildTrackFrom(TrackRequest $trackRequest): TrackModel
    {
        return new TrackModel([
                                  'ipv4' => $trackRequest->getIpv4(),
                                  'ipv6' => $trackRequest->getIpv6(),
                                  'source' => $trackRequest->getSource(),
                                  'userId' => $trackRequest->getUserId(),
                                  'time' => $trackRequest->getTime()
                              ]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(TrackRequest $trackRequest): int
    {
        $result = $this->trackRepository->add($this->buildTrackFrom($trackRequest));

        $this->eventDispatcher->notify(
            'track.add',
            new Event(
                $this,
                EventMessage::factory()->addDescription($this->request->getClientAddress(true))
            )
        );

        return $result->getLastId();
    }

    /**
     * @param ItemSearchDto $itemSearchData
     * @return QueryResult
     */
    public function search(ItemSearchDto $itemSearchData): QueryResult
    {
        return $this->trackRepository->search($itemSearchData, time() - self::TIME_TRACKING);
    }
}
