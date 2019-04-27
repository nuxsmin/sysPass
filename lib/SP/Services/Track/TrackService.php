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

namespace SP\Services\Track;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\TrackData;
use SP\Http\Request;
use SP\Repositories\NoSuchItemException;
use SP\Repositories\Track\TrackRepository;
use SP\Repositories\Track\TrackRequest;
use SP\Services\Service;
use SP\Services\ServiceException;
use SP\Storage\Database\QueryResult;

/**
 * Class TrackService
 *
 * @package SP\Services
 */
final class TrackService extends Service
{
    /**
     * Tiempo para contador de intentos
     */
    const TIME_TRACKING = 600;
    const TIME_TRACKING_MAX_ATTEMPTS = 10;
    const TIME_SLEEP = 0.5;

    /**
     * @var TrackRepository
     */
    protected $trackRepository;
    /**
     * @var Request
     */
    protected $request;

    /**
     * @param string $source
     *
     * @return TrackRequest
     * @throws InvalidArgumentException
     */
    public function getTrackRequest($source)
    {
        $trackRequest = new TrackRequest();
        $trackRequest->time = time() - self::TIME_TRACKING;
        $trackRequest->setTrackIp($this->request->getClientAddress());
        $trackRequest->source = $source;

        return $trackRequest;
    }

    /**
     * @param $id int
     *
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function delete($id)
    {
        if ($this->trackRepository->delete($id) === 0) {
            throw new NoSuchItemException(__u('Track not found'));
        }
    }

    /**
     * @param $id int
     *
     * @throws QueryException
     * @throws ConstraintException
     * @throws NoSuchItemException
     */
    public function unlock($id)
    {
        if ($this->trackRepository->unlock($id) === 0) {
            throw new NoSuchItemException(__u('Track not found'));
        }
    }

    /**
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function clear()
    {
        return $this->trackRepository->clear();
    }

    /**
     * @param $id int
     *
     * @return TrackData
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function getById($id)
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
    public function getAll()
    {
        return $this->trackRepository->getAll()->getDataAsArray();
    }

    /**
     * Comprobar los intentos de login
     *
     * @param TrackRequest $trackRequest
     *
     * @return bool True if delay is performed, false otherwise
     * @throws Exception
     */
    public function checkTracking(TrackRequest $trackRequest)
    {
        try {
            $attempts = $this->getTracksForClientFromTime($trackRequest);

            if ($attempts >= self::TIME_TRACKING_MAX_ATTEMPTS) {
                $delaySeconds = self::TIME_SLEEP * $attempts;

                $this->eventDispatcher->notifyEvent('track.delay',
                    new Event($this, EventMessage::factory()
                        ->addDescription(sprintf(__('Attempts exceeded (%d/%d)'), $attempts, self::TIME_TRACKING_MAX_ATTEMPTS))
                        ->addDetail(__u('Seconds'), $delaySeconds))
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
     * @param TrackRequest $trackRequest
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getTracksForClientFromTime(TrackRequest $trackRequest)
    {
        return $this->trackRepository->getTracksForClientFromTime($trackRequest)->getNumRows();
    }

    /**
     * @param TrackRequest $trackRequest
     *
     * @return int
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function add(TrackRequest $trackRequest)
    {
        if ($trackRequest->getIpv4() === null
            && $trackRequest->getIpv6() === null
        ) {
            throw new ServiceException(__u('IP address not set'));
        }

        $result = $this->trackRepository->add($trackRequest);

        $this->eventDispatcher->notifyEvent('track.add',
            new Event($this, EventMessage::factory()
                ->addDescription($this->request->getClientAddress(true)))
        );

        return $result;
    }

    /**
     * @param ItemSearchData $itemSearchData
     *
     * @return QueryResult
     * @throws ConstraintException
     * @throws QueryException
     */
    public function search(ItemSearchData $itemSearchData)
    {
        return $this->trackRepository->search($itemSearchData);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->trackRepository = $this->dic->get(TrackRepository::class);
        $this->request = $this->dic->get(Request::class);
    }
}