<?php

namespace SP\Services\Track;

use SP\DataModel\TrackData;
use SP\Repositories\Track\TrackRepository;
use SP\Repositories\Track\TrackRequest;
use SP\Services\Service;
use SP\Util\HttpUtil;

/**
 * Class TrackService
 * @package SP\Services
 */
class TrackService extends Service
{
    /**
     * Tiempo para contador de intentos
     */
    const TIME_TRACKING = 600;
    const TIME_TRACKING_MAX_ATTEMPTS = 5;
    const TIME_SLEEP = 0.3;

    /**
     * @var TrackRepository
     */
    protected $trackRepository;

    /**
     * @param TrackRequest $trackRequest
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add(TrackRequest $trackRequest)
    {
        return $this->trackRepository->add($trackRequest);
    }

    /**
     * @param $id int|array
     * @return mixed
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function delete($id)
    {
        return $this->trackRepository->delete($id);
    }

    /**
     * @param TrackData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update(TrackData $itemData)
    {
        return $this->trackRepository->update($itemData);
    }

    /**
     * @param $id int
     * @return TrackData
     */
    public function getById($id)
    {
        return $this->trackRepository->getById($id);
    }

    /**
     * @return TrackData[]
     */
    public function getAll()
    {
        return $this->trackRepository->getAll();
    }

    /**
     * Devuelve los tracks de un cliente desde un tiempo y origen determinados
     *
     * @param TrackRequest $trackRequest
     * @return array
     */
    public function getTracksForClientFromTime(TrackRequest $trackRequest)
    {
        return $this->trackRepository->getTracksForClientFromTime($trackRequest);
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function initialize()
    {
        $this->trackRepository = $this->dic->get(TrackRepository::class);
    }

    /**
     * @param $source
     * @return TrackRequest
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public static function getTrackRequest($source)
    {
        $trackRequest = new TrackRequest();
        $trackRequest->time = time() - self::TIME_TRACKING;
        $trackRequest->setTrackIp(HttpUtil::getClientAddress());
        $trackRequest->source = $source;

        return $trackRequest;
    }
}