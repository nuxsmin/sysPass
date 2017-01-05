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

namespace SP\Mgmt\Notices;

use SP\Core\Exceptions\SPException;
use SP\Core\Session;
use SP\DataModel\NoticeData;
use SP\Mgmt\ItemInterface;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class Notice
 *
 * @package SP\Mgmt\Notices
 */
class Notice extends NoticeBase implements ItemInterface
{
    /**
     * @return $this
     * @throws SPException
     */
    public function add()
    {
        $query = /** @lang SQL */
            'INSERT INTO notices 
            SET notice_type = ?,
            notice_component = ?,
            notice_description = ?,
            notice_date = UNIX_TIMESTAMP(),
            notice_checked = 0,
            notice_userId = ?,
            notice_sticky = ?,
            notice_onlyAdmin = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getNoticeType());
        $Data->addParam($this->itemData->getNoticeComponent());
        $Data->addParam($this->itemData->getNoticeDescription());
        $Data->addParam($this->itemData->getNoticeUserId());
        $Data->addParam($this->itemData->isNoticeSticky());
        $Data->addParam($this->itemData->isNoticeOnlyAdmin());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al crear la notificación'));
        }

        $this->itemData->setNoticeId(DB::$lastId);

        return $this;
    }

    /**
     * @param $id int|array
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        $query = 'DELETE FROM notices WHERE notice_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        if (DB::getQuery($Data) === false) {
            new SPException(SPException::SP_ERROR, _('Error al eliminar la notificación'));
        }

        $this->itemData->setNoticeId(DB::$lastId);

        return $this;
    }

    /**
     * @return $this
     * @throws SPException
     */
    public function update()
    {
        $query = /** @lang SQL */
            'UPDATE notices 
            SET notice_type = ?,
            notice_component = ?,
            notice_description = ?,
            notice_date = UNIX_TIMESTAMP(),
            notice_checked = 0,
            notice_userId = ?,
            notice_sticky = ?,
            notice_onlyAdmin = ? 
            WHERE notice_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getNoticeType());
        $Data->addParam($this->itemData->getNoticeComponent());
        $Data->addParam($this->itemData->getNoticeDescription());
        $Data->addParam($this->itemData->isNoticeChecked());
        $Data->addParam($this->itemData->getNoticeUserId());
        $Data->addParam($this->itemData->isNoticeSticky());
        $Data->addParam($this->itemData->isNoticeOnlyAdmin());
        $Data->addParam($this->itemData->getNoticeId());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al modificar la notificación'));
        }

        $this->itemData->setNoticeId(DB::$lastId);

        return $this;
    }

    /**
     * @param $id int
     * @return NoticeData
     * @throws SPException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT notice_type,
            notice_component,
            notice_description,
            notice_date,
            notice_checked,
            notice_userId,
            notice_sticky,
            notice_onlyAdmin 
            FROM notices 
            WHERE notice_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName($this->getDataModel());
        $Data->addParam($this->itemData->getNoticeId());

        try {
            $queryRes = DB::getResults($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_ERROR, _('Error al obtener la notificación'));
        }

        return $queryRes;
    }

    /**
     * @return NoticeData[]
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT notice_id 
            notice_type,
            notice_component,
            notice_description,
            notice_date,
            notice_checked,
            notice_userId,
            notice_sticky,
            notice_onlyAdmin 
            FROM notices';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName($this->getDataModel());

        try {
            $queryRes = DB::getResultsArray($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_ERROR, _('Error al obtener las notificaciones'));
        }

        return $queryRes;
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
     * Marcar una notificación como leída
     *
     * @return $this
     * @throws SPException
     */
    public function setChecked()
    {
        $query = /** @lang SQL */
            'UPDATE notices SET notice_checked = 1 WHERE notice_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getNoticeId());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al modificar la notificación'));
        }

        $this->itemData->setNoticeId(DB::$lastId);

        return $this;
    }

    /**
     * Devolver las notificaciones de un usuario para una fecha y componente determinados
     * @return mixed
     * @throws SPException
     */
    public function getByUserCurrentDate()
    {
        $query = /** @lang SQL */
            'SELECT notice_type,
            notice_component,
            notice_description,
            notice_date,
            notice_checked,
            notice_userId,
            notice_sticky,
            notice_onlyAdmin 
            FROM notices 
            WHERE notice_component = ? AND 
            (UNIX_TIMESTAMP() - notice_date) <= 86400 AND
            notice_userId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName($this->getDataModel());
        $Data->addParam($this->itemData->getNoticeComponent());
        $Data->addParam($this->itemData->getNoticeUserId());

        try {
            $queryRes = DB::getResultsArray($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_ERROR, _('Error al obtener las notificaciones'));
        }

        return $queryRes;
    }

    /**
     * @return NoticeData[]
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAllForUser()
    {
        $query = /** @lang SQL */
            'SELECT notice_id,
            notice_type,
            notice_component,
            notice_description,
            FROM_UNIXTIME(notice_date) AS notice_date,
            notice_checked,
            notice_userId,
            notice_sticky,
            notice_onlyAdmin 
            FROM notices 
            WHERE notice_userId = ? OR notice_onlyAdmin = 0 
            ORDER BY notice_date DESC ';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName($this->getDataModel());
        $Data->addParam(Session::getUserData()->getUserId());

        try {
            $queryRes = DB::getResultsArray($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_ERROR, _('Error al obtener las notificaciones'));
        }

        return $queryRes;
    }

    /**
     * @return NoticeData[]
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAllActiveForUser()
    {
        $query = /** @lang SQL */
            'SELECT notice_id,
            notice_type,
            notice_component,
            notice_description,
            FROM_UNIXTIME(notice_date) AS notice_date,
            notice_checked,
            notice_userId,
            notice_sticky,
            notice_onlyAdmin 
            FROM notices 
            WHERE notice_userId = ? AND notice_onlyAdmin = 0
            AND (notice_checked = 0 OR notice_sticky = 1)
            ORDER BY notice_date DESC ';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName($this->getDataModel());
        $Data->addParam(Session::getUserData()->getUserId());

        try {
            $queryRes = DB::getResultsArray($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_ERROR, _('Error al obtener las notificaciones'));
        }

        return $queryRes;
    }
}