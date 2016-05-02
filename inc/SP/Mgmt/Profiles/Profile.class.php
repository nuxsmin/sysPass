<?php

/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Mgmt\Profiles;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\Exceptions\SPException;
use SP\DataModel\ProfileBaseData;
use SP\DataModel\ProfileData;
use SP\Html\Html;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\ItemInterface;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Checks;
use SP\Util\Util;


/**
 * Esta clase es la encargada de realizar las operaciones sobre los perfiles de usuarios.
 */
class Profile extends ProfileBase implements ItemInterface
{
    /**
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()){
            throw new SPException(SPException::SP_INFO, _('Nombre de perfil duplicado'));
        }

        $query = /** @lang SQL */
            'INSERT INTO usrProfiles SET
            userprofile_name = ?,
            userprofile_profile = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserprofileName());
        $Data->addParam(serialize($this->itemData));

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al crear perfil'));
        }

        $this->itemData->setUserprofileId(DB::getLastId());

        $Log = new Log(_('Nuevo Perfil'));
        $Log->addDetails(Html::strongText(_('Nombre')), $this->itemData->getUserprofileName());
        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @param $id int
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        if ($this->checkInUse($id)) {
            throw new SPException(SPException::SP_INFO, _('Perfil en uso'));
        }

        $oldProfile = $this->getById($id)->getItemData();

        $query = /** @lang SQL */
            'DELETE FROM usrProfiles WHERE userprofile_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al eliminar perfil'));
        }

        $Log = new Log(_('Eliminar Perfil'));
        $Log->addDetails(Html::strongText(_('Nombre')), $oldProfile->getUserprofileName());
        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @return $this
     * @throws SPException
     */
    public function update()
    {
        if ($this->checkDuplicatedOnUpdate()){
            throw new SPException(SPException::SP_INFO, _('Nombre de perfil duplicado'));
        }

        $oldProfileName = $this->getById($this->itemData->getUserprofileId())->getItemData();

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

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al modificar perfil'));
        }

        $Log = new Log(_('Modificar Perfil'));
        $Log->addDetails(Html::strongText(_('Nombre')), $oldProfileName->getUserprofileName() . ' > ' . $this->itemData->getUserprofileName());
        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @param $id int
     * @return $this
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
         * @var ProfileBaseData $ProfileData
         * @var ProfileData $Profile
         */
        $ProfileData = DB::getResults($Data);
        $Profile = unserialize($ProfileData->getUserprofileProfile());

        if (get_class($Profile) === '__PHP_Incomplete_Class') {
            $Profile = Util::castToClass($this->getDataModel(), $Profile);
        }

        $Profile->setUserprofileId($ProfileData->getUserprofileId());
        $Profile->setUserprofileName($ProfileData->getUserprofileName());

        $this->itemData = $Profile;

        return $this;
    }

    /**
     * @return ProfileData[]
     */
    public function getAll()
    {
        if (Checks::demoIsEnabled()) {
            $query = /** @lang SQL */
                'SELECT userprofile_id, userprofile_name
                FROM usrProfiles
                WHERE userprofile_name <> "Admin"
                AND userprofile_name <> "Demo"
                ORDER BY userprofile_name';
        } else {
            $query = /** @lang SQL */
                'SELECT userprofile_id, userprofile_name
                FROM usrProfiles
                ORDER BY userprofile_name';
        }

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);

        DB::setReturnArray();

        return DB::getResults($Data);
    }

    /**
     * @param $id int
     * @return bool
     */
    public function checkInUse($id)
    {
        $query = /** @lang SQL */
            'SELECT user_profileId FROM usrData WHERE user_profileId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        DB::getQuery($Data);

        return (DB::$lastNumRows > 0);
    }

    /**
     * @return bool
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

        return (DB::$lastNumRows > 0);
    }

    /**
     * @return bool
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

        return (DB::$lastNumRows > 0);
    }
}
