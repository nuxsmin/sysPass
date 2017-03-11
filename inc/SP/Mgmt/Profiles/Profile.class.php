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
use SP\Core\Session;
use SP\DataModel\ProfileBaseData;
use SP\DataModel\ProfileData;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemSelectInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DB;
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
            'INSERT INTO usrProfiles SET
            userprofile_name = ?,
            userprofile_profile = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserprofileName());
        $Data->addParam(serialize($this->itemData));
        $Data->setOnErrorMessage(__('Error al crear perfil', false));

        DB::getQuery($Data);

        $this->itemData->setUserprofileId(DB::getLastId());

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkDuplicatedOnAdd()
    {
        $query = /** @lang SQL */
            'SELECT userprofile_name
            FROM usrProfiles
            WHERE UPPER(userprofile_name) = ?';

        $Data = new QueryData();
        $Data->addParam($this->itemData->getUserprofileName());
        $Data->setQuery($query);

        DB::getQuery($Data);

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
            'DELETE FROM usrProfiles WHERE userprofile_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al eliminar perfil', false));

        DB::getQuery($Data);

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

        DB::getQuery($Data);

        return ($Data->getQueryNumRows() > 0);
    }

    /**
     * @param $id int
     * @return ProfileData
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT userprofile_id,
            userprofile_name,
            userprofile_profile
            FROM usrProfiles
            WHERE userprofile_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($id);

        /**
         * @var ProfileBaseData $queryRes
         * @var ProfileData     $Profile
         */
        $queryRes = DB::getResults($Data);

        $Profile = Util::castToClass($this->getDataModel(), $queryRes->getUserprofileProfile());
        $Profile->setUserprofileId($queryRes->getUserprofileId());
        $Profile->setUserprofileName($queryRes->getUserprofileName());

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
            'UPDATE usrProfiles SET
          userprofile_name = ?,
          userprofile_profile = ?
          WHERE userprofile_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserprofileName());
        $Data->addParam(serialize($this->itemData));
        $Data->addParam($this->itemData->getUserprofileId());
        $Data->setOnErrorMessage(__('Error al modificar perfil', false));

        DB::getQuery($Data);

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
            'SELECT userprofile_name
            FROM usrProfiles
            WHERE UPPER(userprofile_name) = ?
            AND userprofile_id <> ?';

        $Data = new QueryData();
        $Data->addParam($this->itemData->getUserprofileName());
        $Data->addParam($this->itemData->getUserprofileId());
        $Data->setQuery($query);

        DB::getQuery($Data);

        return ($Data->getQueryNumRows() > 0);
    }

    /**
     * Actualizar el perfil de la sesión
     */
    protected function updateSessionProfile()
    {
        if (Session::getUserProfile()->getUserprofileId() === $this->itemData->getUserprofileId()) {
            Session::setUserProfile($this->itemData);
        }
    }

    /**
     * @return ProfileData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT userprofile_id, userprofile_name
                FROM usrProfiles
                ORDER BY userprofile_name';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);

        return DB::getResultsArray($Data);
    }

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param array $ids
     * @return ProfileBaseData[]
     */
    public function getByIdBatch(array $ids)
    {
        if (count($ids) === 0) {
            return [];
        }

        $query = /** @lang SQL */
            'SELECT userprofile_id,
            userprofile_name
            FROM usrProfiles
            WHERE userprofile_id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DB::getResultsArray($Data);
    }
}
