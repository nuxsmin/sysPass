<?php

namespace SP\Repositories\Track;

use SP\DataModel\TrackData;
use SP\Repositories\Repository;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class TrackRepository
 * @package SP\Repositories\Track
 */
class TrackRepository extends Repository
{
    /**
     * @param TrackRequest $trackRequest
     * @return mixed
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function add(TrackRequest $trackRequest)
    {
        $query = /** @lang SQL */
            'INSERT INTO Track SET 
            userId = ?, 
            source = ?, 
            time = UNIX_TIMESTAMP(),
            ipv4 = ?,
            ipv6 = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($trackRequest->userId);
        $queryData->addParam($trackRequest->source);
        $queryData->addParam($trackRequest->getIpv4());
        $queryData->addParam($trackRequest->getIpv6());
        $queryData->setOnErrorMessage(__u('Error al crear track'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getLastId();
    }

    /**
     * @param $id int|array
     * @return mixed
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function delete($id)
    {
        $queryData = new QueryData();
        $queryData->setQuery('DELETE FROM Track WHERE id = ? LIMIT 1');
        $queryData->addParam($id);
        $queryData->setOnErrorMessage(__u('Error al eliminar track'));

        DbWrapper::getQuery($queryData, $this->db);

        return $this->db->getNumRows();
    }

    /**
     * @param TrackData $itemData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function update(TrackData $itemData)
    {
        $query = /** @lang SQL */
            'UPDATE Track SET 
            track_userId = ?, 
            source = ?, 
            time = UNIX_TIMESTAMP(),
            ipv4 = ?,
            ipv6 = ? 
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($itemData->getUserId());
        $queryData->addParam($itemData->getSource());
        $queryData->addParam($itemData->getTrackIpv4Bin());
        $queryData->addParam($itemData->getTrackIpv6Bin());
        $queryData->addParam($itemData->getId());
        $queryData->setOnErrorMessage(__u('Error al actualizar track'));

        return DbWrapper::getQuery($queryData, $this->db);
    }

    /**
     * @param $id int
     * @return TrackData
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT id, 
            userId, 
            source, 
            time,
            ipv4,
            ipv6 
            FROM Track 
            WHERE id = ? LIMIT 1';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($id);
        $queryData->setMapClassName(TrackData::class);
        $queryData->setOnErrorMessage(__u('Error al obtener track'));

        return DbWrapper::getResults($queryData, $this->db);
    }

    /**
     * @return TrackData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT id, 
            userId, 
            source, 
            time,
            ipv4,
            ipv6 FROM Track';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->setMapClassName(TrackData::class);
        $queryData->setOnErrorMessage(__u('Error al obtener tracks'));

        return DbWrapper::getResultsArray($queryData);
    }

    /**
     * Devuelve los tracks de un cliente desde un tiempo y origen determinados
     *
     * @param TrackRequest $trackRequest
     * @return array
     */
    public function getTracksForClientFromTime(TrackRequest $trackRequest)
    {
        $query = /** @lang SQL */
            'SELECT id, userId 
            FROM Track 
            WHERE `time` >= ? 
            AND (ipv4 = ? OR ipv6 = ?) 
            AND `source` = ?';

        $queryData = new QueryData();
        $queryData->setQuery($query);
        $queryData->addParam($trackRequest->time);
        $queryData->addParam($trackRequest->getIpv4());
        $queryData->addParam($trackRequest->getIpv6());
        $queryData->addParam($trackRequest->source);
        $queryData->setMapClassName(TrackData::class);
        $queryData->setOnErrorMessage(__u('Error al obtener tracks'));

        return DbWrapper::getResultsArray($queryData, $this->db);
    }
}