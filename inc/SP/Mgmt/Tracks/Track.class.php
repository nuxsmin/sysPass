<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Mgmt\Tracks;

use SP\DataModel\TrackData;
use SP\Mgmt\ItemInterface;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class Track
 *
 * @package SP\Mgmt\Tracks
 * @property TrackData $itemData
 */
class Track extends TrackBase implements ItemInterface
{
    /**
     * @return mixed
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function add()
    {
        $query = /** @lang SQL */
            'INSERT INTO track SET 
            track_userId = ?, 
            track_source = ?, 
            track_time = UNIX_TIMESTAMP(),
            track_ipv4 = ?,
            track_ipv6 = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getTrackUserId());
        $Data->addParam($this->itemData->getTrackSource());
        $Data->addParam($this->itemData->getTrackIpv4Bin());
        $Data->addParam($this->itemData->getTrackIpv6Bin());
        $Data->setOnErrorMessage(__('Error al crear track', false));

        DB::getQuery($Data);

        $this->itemData->setTrackId(DB::$lastId);

        return $this;
    }

    /**
     * @param $id int|array
     * @return mixed
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM track WHERE track_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getTrackId());
        $Data->setOnErrorMessage(__('Error al eliminar track', false));

        return DB::getQuery($Data);
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function update()
    {
        $query = /** @lang SQL */
            'UPDATE track SET 
            track_userId = ?, 
            track_source = ?, 
            track_time = UNIX_TIMESTAMP(),
            track_ipv4 = ?,
            track_ipv6 = ? 
            WHERE track_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getTrackUserId());
        $Data->addParam($this->itemData->getTrackSource());
        $Data->addParam($this->itemData->getTrackIpv4Bin());
        $Data->addParam($this->itemData->getTrackIpv6Bin());
        $Data->addParam($this->itemData->getTrackId());
        $Data->setOnErrorMessage(__('Error al actualizar track', false));

        return DB::getQuery($Data);
    }

    /**
     * @param $id int
     * @return mixed
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT track_id, 
            track_userId, 
            track_source, 
            track_time,
            track_ipv4,
            track_ipv6 
            FROM track 
            WHERE track_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getTrackId());
        $Data->setOnErrorMessage(__('Error al obtener track', false));

        return DB::getResults($Data);
    }

    /**
     * @return array
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT track_id, 
            track_userId, 
            track_source, 
            track_time,
            track_ipv4,
            track_ipv6 FROM track';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getTrackId());
        $Data->setOnErrorMessage(__('Error al obtener tracks', false));

        return DB::getResultsArray($Data);
    }

    /**
     * @param $id int
     * @return mixed
     */
    public function checkInUse($id)
    {
        // TODO: Implement checkInUse() method.
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnUpdate()
    {
        // TODO: Implement checkDuplicatedOnUpdate() method.
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnAdd()
    {
        // TODO: Implement checkDuplicatedOnAdd() method.
    }

    /**
     * Eliminar elementos en lote
     *
     * @param array $ids
     * @return $this
     */
    public function deleteBatch(array $ids)
    {
        // TODO: Implement deleteBatch() method.
    }

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param array $ids
     * @return mixed
     */
    public function getByIdBatch(array $ids)
    {
        // TODO: Implement getByIdBatch() method.
    }


    /**
     * Devuelve los tracks de un cliente desde un tiempo y origen determinados
     *
     * @param $time
     * @return array
     */
    public function getTracksForClientFromTime($time)
    {
        $query = /** @lang SQL */
            'SELECT track_id, track_userId 
            FROM track 
            WHERE track_time >= ? 
            AND (track_ipv4 = ? OR track_ipv6 = ?) 
            AND track_source = ?';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($time);
        $Data->addParam($this->itemData->getTrackIpv4Bin());
        $Data->addParam($this->itemData->getTrackIpv6Bin());
        $Data->addParam($this->itemData->getTrackSource());
        $Data->setOnErrorMessage(__('Error al obtener tracks', false));

        return DB::getResultsArray($Data);
    }
}