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

namespace SP\Mgmt\Profiles;

defined('APP_ROOT') || die();

use SP\Core\Exceptions\SPException;
use SP\DataModel\UserProfileData;
use SP\DataModel\ProfileData;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemSelectInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Esta clase es la encargada de realizar las operaciones sobre los perfiles de usuarios.
 *
 * @property ProfileData $itemData
 */
class Profile extends ProfileBase implements ItemInterface, ItemSelectInterface
{
    use ItemTrait;

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()) {
            throw new SPException(SPException::SP_INFO, __('Nombre de perfil duplicado', false));
        }

        $query = /** @lang SQL */
            'INSERT INTO UserProfile SET
            name = ?,
            profile = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getName());
        $Data->addParam(serialize($this->itemData));
        $Data->setOnErrorMessage(__('Error al crear perfil', false));

        DbWrapper::getQuery($Data);

        $this->itemData->setId(DbWrapper::getLastId());

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkDuplicatedOnAdd()
    {
        $query = /** @lang SQL */
            'SELECT name
            FROM UserProfile
            WHERE UPPER(name) = ?';

        $Data = new QueryData();
        $Data->addParam($this->itemData->getName());
        $Data->setQuery($query);

        DbWrapper::getQuery($Data);

        return ($Data->getQueryNumRows() > 0);
    }

    /**
     * @param $id int
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        if ($this->checkInUse($id)) {
            throw new SPException(SPException::SP_INFO, __('Perfil en uso', false));
        }

        $query = /** @lang SQL */
            'DELETE FROM UserProfile WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al eliminar perfil', false));

        DbWrapper::getQuery($Data);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __('Perfil no encontrado', false));
        }

        return $this;
    }

    /**
     * @param $id int
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkInUse($id)
    {
        $query = /** @lang SQL */
            'SELECT user_profileId FROM usrData WHERE user_profileId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        DbWrapper::getQuery($Data);

        return ($Data->getQueryNumRows() > 0);
    }

    /**
     * @param $id int
     * @return ProfileData
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT id,
            name,
            profile
            FROM UserProfile
            WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($id);

        /**
         * @var UserProfileData $queryRes
         * @var ProfileData     $Profile
         */
        $queryRes = DbWrapper::getResults($Data);

        $Profile = Util::unserialize($this->getDataModel(), $queryRes->getProfile());
        $Profile->setId($queryRes->getId());
        $Profile->setName($queryRes->getName());

        return $Profile;
    }

    /**
     * @return $this
     * @throws SPException
     */
    public function update()
    {
        if ($this->checkDuplicatedOnUpdate()) {
            throw new SPException(SPException::SP_INFO, __('Nombre de perfil duplicado', false));
        }

        $query = /** @lang SQL */
            'UPDATE UserProfile SET
          name = ?,
          profile = ?
          WHERE id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getName());
        $Data->addParam(serialize($this->itemData));
        $Data->addParam($this->itemData->getId());
        $Data->setOnErrorMessage(__('Error al modificar perfil', false));

        DbWrapper::getQuery($Data);

        if ($Data->getQueryNumRows() > 0) {
            $this->updateSessionProfile();
        }

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function checkDuplicatedOnUpdate()
    {
        $query = /** @lang SQL */
            'SELECT name
            FROM UserProfile
            WHERE UPPER(name) = ?
            AND id <> ?';

        $Data = new QueryData();
        $Data->addParam($this->itemData->getName());
        $Data->addParam($this->itemData->getId());
        $Data->setQuery($query);

        DbWrapper::getQuery($Data);

        return ($Data->getQueryNumRows() > 0);
    }

    /**
     * Actualizar el perfil de la sesión
     */
    protected function updateSessionProfile()
    {
        if ($this->session->getUserProfile()->getId() === $this->itemData->getId()) {
            $this->session->setUserProfile($this->itemData);
        }
    }

    /**
     * @return ProfileData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT id, name
                FROM UserProfile
                ORDER BY name';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);

        return DbWrapper::getResultsArray($Data);
    }

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param array $ids
     * @return UserProfileData[]
     */
    public function getByIdBatch(array $ids)
    {
        if (count($ids) === 0) {
            return [];
        }

        $query = /** @lang SQL */
            'SELECT id,
            name
            FROM UserProfile
            WHERE id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DbWrapper::getResultsArray($Data);
    }
}
