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
use SP\Storage\DbWrapper;
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
            'INSERT INTO Track SET 
            userId = ?, 
            source = ?, 
            time = UNIX_TIMESTAMP(),
            ipv4 = ?,
            ipv6 = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserId());
        $Data->addParam($this->itemData->getSource());
        $Data->addParam($this->itemData->getTrackIpv4Bin());
        $Data->addParam($this->itemData->getTrackIpv6Bin());
        $Data->setOnErrorMessage(__('Error al crear track', false));

        DbWrapper::getQuery($Data);

        $this->itemData->setId(DbWrapper::$lastId);

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
            'DELETE FROM Track WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getId());
        $Data->setOnErrorMessage(__('Error al eliminar track', false));

        return DbWrapper::getQuery($Data);
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function update()
    {
        $query = /** @lang SQL */
            'UPDATE Track SET 
            track_userId = ?, 
            source = ?, 
            time = UNIX_TIMESTAMP(),
            ipv4 = ?,
            ipv6 = ? 
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserId());
        $Data->addParam($this->itemData->getSource());
        $Data->addParam($this->itemData->getTrackIpv4Bin());
        $Data->addParam($this->itemData->getTrackIpv6Bin());
        $Data->addParam($this->itemData->getId());
        $Data->setOnErrorMessage(__('Error al actualizar track', false));

        return DbWrapper::getQuery($Data);
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
            'SELECT id, 
            userId, 
            source, 
            time,
            ipv4,
            ipv6 
            FROM Track 
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getId());
        $Data->setOnErrorMessage(__('Error al obtener track', false));

        return DbWrapper::getResults($Data);
    }

    /**
     * @return array
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
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

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getId());
        $Data->setOnErrorMessage(__('Error al obtener tracks', false));

        return DbWrapper::getResultsArray($Data);
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
            'SELECT id, userId 
            FROM Track 
            WHERE time >= ? 
            AND (ipv4 = ? OR ipv6 = ?) 
            AND source = ?';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($time);
        $Data->addParam($this->itemData->getTrackIpv4Bin());
        $Data->addParam($this->itemData->getTrackIpv6Bin());
        $Data->addParam($this->itemData->getSource());
        $Data->setOnErrorMessage(__('Error al obtener tracks', false));

        return DbWrapper::getResultsArray($Data);
    }
}