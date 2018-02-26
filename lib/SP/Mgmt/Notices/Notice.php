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
use SP\Core\SessionFactory;
use SP\DataModel\NotificationData;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

/**
 * Class Notice
 *
 * @package SP\Mgmt\Notices
 * @property NotificationData $itemData
 * @method NotificationData getItemData()
 */
class Notice extends NoticeBase implements ItemInterface
{
    use ItemTrait;

    /**
     * @return $this
     * @throws SPException
     */
    public function add()
    {
        $query = /** @lang SQL */
            'INSERT INTO Notification 
            SET type = ?,
            component = ?,
            description = ?,
            date = UNIX_TIMESTAMP(),
            checked = 0,
            userId = ?,
            sticky = ?,
            onlyAdmin = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getType());
        $Data->addParam($this->itemData->getComponent());
        $Data->addParam($this->itemData->getDescription());
        $Data->addParam($this->itemData->getUserId());
        $Data->addParam($this->itemData->isSticky());
        $Data->addParam($this->itemData->isOnlyAdmin());
        $Data->setOnErrorMessage(__('Error al crear la notificación', false));

        DbWrapper::getQuery($Data);

        $this->itemData->setId(DbWrapper::$lastId);

        return $this;
    }

    /**
     * @param $id int
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        $query = 'DELETE FROM Notification WHERE id = ? AND BIN(sticky) = 0 LIMIT 1';

        if (SessionFactory::getUserData()->isIsAdminApp()) {
            $query = 'DELETE FROM Notification WHERE id = ? LIMIT 1';
        }

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al eliminar la notificación', false));

        DbWrapper::getQuery($Data);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(__('Notificación no encontrada', false), SPException::INFO);
        }

        return $this;
    }

    /**
     * @return $this
     * @throws SPException
     */
    public function update()
    {
        $query = /** @lang SQL */
            'UPDATE Notification 
            SET type = ?,
            component = ?,
            description = ?,
            date = UNIX_TIMESTAMP(),
            checked = 0,
            userId = ?,
            sticky = ?,
            onlyAdmin = ? 
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getType());
        $Data->addParam($this->itemData->getComponent());
        $Data->addParam($this->itemData->getDescription());
        $Data->addParam($this->itemData->getUserId());
        $Data->addParam($this->itemData->isSticky());
        $Data->addParam($this->itemData->isOnlyAdmin());
        $Data->addParam($this->itemData->getId());
        $Data->setOnErrorMessage(__('Error al modificar la notificación', false));

        DbWrapper::getQuery($Data);

        return $this;
    }

    /**
     * @param $id int
     * @return NotificationData
     * @throws SPException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT id, 
            type,
            component,
            description,
            FROM_UNIXTIME(date) AS notice_date,
            userId,
            BIN(checked) AS notice_checked,
            BIN(sticky) as notice_sticky,
            BIN(onlyAdmin) AS notice_onlyAdmin 
            FROM Notification 
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName($this->getDataModel());
        $Data->addParam($id);

        try {
            $queryRes = DbWrapper::getResults($Data);
        } catch (SPException $e) {
            throw new SPException(__('Error al obtener la notificación', false), SPException::ERROR);
        }

        return $queryRes;
    }

    /**
     * @return NotificationData[]
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT id 
            notice_type,
            component,
            description,
            FROM_UNIXTIME(date) AS notice_date,
            userId,
            BIN(checked) AS notice_checked,
            BIN(sticky) as notice_sticky,
            BIN(onlyAdmin) AS notice_onlyAdmin 
            FROM Notification';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName($this->getDataModel());

        try {
            $queryRes = DbWrapper::getResultsArray($Data);
        } catch (SPException $e) {
            throw new SPException(__('Error al obtener las notificaciones', false), SPException::ERROR);
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
     * @param $id
     * @return $this
     * @throws SPException
     */
    public function setChecked($id)
    {
        $query = /** @lang SQL */
            'UPDATE Notification SET checked = 1 WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al modificar la notificación', false));

        DbWrapper::getQuery($Data);

        $this->itemData->setId(DbWrapper::$lastId);

        return $this;
    }

    /**
     * Devolver las notificaciones de un usuario para una fecha y componente determinados
     *
     * @return mixed
     * @throws SPException
     */
    public function getByUserCurrentDate()
    {
        $query = /** @lang SQL */
            'SELECT type,
            component,
            description,
            date,
            userId,
            BIN(checked) AS notice_checked,
            BIN(sticky) as notice_sticky,
            BIN(onlyAdmin) AS notice_onlyAdmin 
            FROM Notification 
            WHERE component = ? AND 
            (UNIX_TIMESTAMP() - date) <= 86400 AND
            userId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName($this->getDataModel());
        $Data->addParam($this->itemData->getComponent());
        $Data->addParam($this->itemData->getUserId());

        try {
            $queryRes = DbWrapper::getResultsArray($Data);
        } catch (SPException $e) {
            throw new SPException(__('Error al obtener las notificaciones', false), SPException::ERROR);
        }

        return $queryRes;
    }

    /**
     * @return NotificationData[]
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getAllForUser()
    {
        $query = /** @lang SQL */
            'SELECT id,
            type,
            component,
            description,
            FROM_UNIXTIME(date) AS notice_date,
            userId,
            BIN(checked) AS notice_checked,
            BIN(sticky) as notice_sticky,
            BIN(onlyAdmin) AS notice_onlyAdmin 
            FROM Notification 
            WHERE userId = ? OR (userId = NULL AND BIN(onlyAdmin) = 0) OR BIN(sticky) = 1
            ORDER BY date DESC ';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName($this->getDataModel());
        $Data->addParam(SessionFactory::getUserData()->getId());

        try {
            $queryRes = DbWrapper::getResultsArray($Data);
        } catch (SPException $e) {
            throw new SPException(__('Error al obtener las notificaciones', false), SPException::ERROR);
        }

        return $queryRes;
    }

    /**
     * @return NotificationData[]
     * @throws SPException
     */
    public function getAllActiveForUser()
    {
        $query = /** @lang SQL */
            'SELECT id,
            type,
            component,
            description,
            FROM_UNIXTIME(date) AS notice_date,
            userId,
            BIN(checked) AS notice_checked,
            BIN(sticky) as notice_sticky,
            BIN(onlyAdmin) AS notice_onlyAdmin 
            FROM Notification 
            WHERE (userId = ? OR BIN(sticky) = 1) 
            AND BIN(onlyAdmin) = 0 
            AND BIN(checked) = 0
            ORDER BY date DESC ';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName($this->getDataModel());
        $Data->addParam(SessionFactory::getUserData()->getId());

        try {
            $queryRes = DbWrapper::getResultsArray($Data);
        } catch (SPException $e) {
            throw new SPException(__('Error al obtener las notificaciones', false), SPException::ERROR);
        }

        return $queryRes;
    }

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param array $ids
     * @return mixed
     */
    public function getByIdBatch(array $ids)
    {
        $query = /** @lang SQL */
            'SELECT id, 
            type,
            component,
            description,
            FROM_UNIXTIME(date) AS notice_date,
            userId,
            BIN(checked) AS notice_checked,
            BIN(sticky) as notice_sticky,
            BIN(onlyAdmin) AS notice_onlyAdmin 
            FROM Notification 
            WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName($this->getDataModel());
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data);
    }
}