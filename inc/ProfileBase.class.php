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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase ProfileBase para la definición de perfiles de acceso de usuarios
 *
 * @package SP
 */
abstract class ProfileBase
{
    /**
     * @var int
     */
    protected $_id = 0;
    /**
     * @var string
     */
    protected $_name = '';
    /**
     * @var bool
     */
    protected $_accView = false;
    /**
     * @var bool
     */
    protected $_accViewPass = false;
    /**
     * @var bool
     */
    protected $_accViewHistory = false;
    /**
     * @var bool
     */
    protected $_accEdit = false;
    /**
     * @var bool
     */
    protected $_accEditPass = false;
    /**
     * @var bool
     */
    protected $_accAdd = false;
    /**
     * @var bool
     */
    protected $_accDelete = false;
    /**
     * @var bool
     */
    protected $_accFiles = false;
    /**
     * @var bool
     */
    protected $_configGeneral = false;
    /**
     * @var bool
     */
    protected $_configEncryption = false;
    /**
     * @var bool
     */
    protected $_configBackup = false;
    /**
     * @var bool
     */
    protected $_configImport = false;
    /**
     * @var bool
     */
    protected $_mgmUsers = false;
    /**
     * @var bool
     */
    protected $_mgmGroups = false;
    /**
     * @var bool
     */
    protected $_mgmProfiles = false;
    /**
     * @var bool
     */
    protected $_mgmCategories = false;
    /**
     * @var bool
     */
    protected $_mgmCustomers = false;
    /**
     * @var bool
     */
    protected $_evl = false;

    /**
     * Obtener los datos de un perfil
     *
     * @param $id int El id del perfil a obtener
     * @return array|Profile
     * @throws SPException
     */
    public static function getProfile($id)
    {
        $query = 'SELECT userprofile_profile FROM usrProfiles WHERE userprofile_id = :id LIMIT 1';

        $data['id'] = $id;

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return false;
        }

        $profile = unserialize($queryRes->userprofile_profile);

        if (!$profile instanceof Profile) {
            throw new SPException(SPException::SP_CRITICAL, _('Datos de perfil incorrectos'));
        }

        return $profile;
    }

    /**
     * Obtener los perfiles disponibles
     *
     * @return array|bool
     */
    public static function getProfiles()
    {
        $query = 'SELECT userprofile_id, userprofile_name FROM usrProfiles ORDER BY userprofile_name';

        DB::setReturnArray();

        return DB::getResults($query, __FUNCTION__);
    }

    /**
     * @return boolean
     */
    public function isAccView()
    {
        return $this->_accView;
    }

    /**
     * @param boolean $accView
     */
    public function setAccView($accView)
    {
        $this->_accView = (bool)$accView;
    }

    /**
     * @return boolean
     */
    public function isAccViewPass()
    {
        return $this->_accViewPass;
    }

    /**
     * @param boolean $accViewPass
     */
    public function setAccViewPass($accViewPass)
    {
        $this->_accViewPass = (bool)$accViewPass;
    }

    /**
     * @return boolean
     */
    public function isAccViewHistory()
    {
        return $this->_accViewHistory;
    }

    /**
     * @param boolean $accViewHistory
     */
    public function setAccViewHistory($accViewHistory)
    {
        $this->_accViewHistory = (bool)$accViewHistory;
    }

    /**
     * @return boolean
     */
    public function isAccEdit()
    {
        return $this->_accEdit;
    }

    /**
     * @param boolean $accEdit
     */
    public function setAccEdit($accEdit)
    {
        $this->_accEdit = (bool)$accEdit;
    }

    /**
     * @return boolean
     */
    public function isAccEditPass()
    {
        return $this->_accEditPass;
    }

    /**
     * @param boolean $accEditPass
     */
    public function setAccEditPass($accEditPass)
    {
        $this->_accEditPass = (bool)$accEditPass;
    }

    /**
     * @return boolean
     */
    public function isAccAdd()
    {
        return $this->_accAdd;
    }

    /**
     * @param boolean $accAdd
     */
    public function setAccAdd($accAdd)
    {
        $this->_accAdd = (bool)$accAdd;
    }

    /**
     * @return boolean
     */
    public function isAccDelete()
    {
        return $this->_accDelete;
    }

    /**
     * @param boolean $accDelete
     */
    public function setAccDelete($accDelete)
    {
        $this->_accDelete = (bool)$accDelete;
    }

    /**
     * @return boolean
     */
    public function isAccFiles()
    {
        return $this->_accFiles;
    }

    /**
     * @param boolean $accFiles
     */
    public function setAccFiles($accFiles)
    {
        $this->_accFiles = (bool)$accFiles;
    }

    /**
     * @return boolean
     */
    public function isConfigGeneral()
    {
        return $this->_configGeneral;
    }

    /**
     * @param boolean $configGeneral
     */
    public function setConfigGeneral($configGeneral)
    {
        $this->_configGeneral = (bool)$configGeneral;
    }

    /**
     * @return boolean
     */
    public function isConfigEncryption()
    {
        return $this->_configEncryption;
    }

    /**
     * @param boolean $configEncryption
     */
    public function setConfigEncryption($configEncryption)
    {
        $this->_configEncryption = (bool)$configEncryption;
    }

    /**
     * @return boolean
     */
    public function isConfigBackup()
    {
        return $this->_configBackup;
    }

    /**
     * @param boolean $configBackup
     */
    public function setConfigBackup($configBackup)
    {
        $this->_configBackup = (bool)$configBackup;
    }

    /**
     * @return boolean
     */
    public function isConfigImport()
    {
        return $this->_configImport;
    }

    /**
     * @param boolean $configImport
     */
    public function setConfigImport($configImport)
    {
        $this->_configImport = (bool)$configImport;
    }

    /**
     * @return boolean
     */
    public function isMgmUsers()
    {
        return $this->_mgmUsers;
    }

    /**
     * @param boolean $mgmUsers
     */
    public function setMgmUsers($mgmUsers)
    {
        $this->_mgmUsers = (bool)$mgmUsers;
    }

    /**
     * @return boolean
     */
    public function isMgmGroups()
    {
        return $this->_mgmGroups;
    }

    /**
     * @param boolean $mgmGroups
     */
    public function setMgmGroups($mgmGroups)
    {
        $this->_mgmGroups = (bool)$mgmGroups;
    }

    /**
     * @return boolean
     */
    public function isMgmProfiles()
    {
        return $this->_mgmProfiles;
    }

    /**
     * @param boolean $mgmProfiles
     */
    public function setMgmProfiles($mgmProfiles)
    {
        $this->_mgmProfiles = (bool)$mgmProfiles;
    }

    /**
     * @return boolean
     */
    public function isMgmCategories()
    {
        return $this->_mgmCategories;
    }

    /**
     * @param boolean $mgmCategories
     */
    public function setMgmCategories($mgmCategories)
    {
        $this->_mgmCategories = (bool)$mgmCategories;
    }

    /**
     * @return boolean
     */
    public function isMgmCustomers()
    {
        return $this->_mgmCustomers;
    }

    /**
     * @param boolean $mgmCustomers
     */
    public function setMgmCustomers($mgmCustomers)
    {
        $this->_mgmCustomers = (bool)$mgmCustomers;
    }

    /**
     * @return boolean
     */
    public function isEvl()
    {
        return $this->_evl;
    }

    /**
     * @param boolean $evl
     */
    public function setEvl($evl)
    {
        $this->_evl = (bool)$evl;
    }

    /**
     * Añadir un nuevo perfil.
     *
     * @return bool
     */
    public function profileAdd()
    {
        $query = 'INSERT INTO usrProfiles SET '
            . 'userprofile_name = :name,'
            . 'userprofile_profile = :profile';

        $data['name'] = $this->getName();
        $data['profile'] = serialize($this);

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        $this->setId(DB::getLastId());

        Log::writeNewLogAndEmail(_('Nuevo Perfil'), Html::strongText(_('Perfil') . ': ') . $this->getName());

        return true;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * Eliminar un perfil.
     *
     * @return bool
     */
    public function profileDelete()
    {
        $oldProfileName = static::getProfileNameById($this->getId());

        $query = 'DELETE FROM usrProfiles WHERE userprofile_id = :id LIMIT 1';

        $data['id'] = $this->getId();

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        Log::writeNewLogAndEmail(_('Eliminar Perfil'), Html::strongText(_('Perfil') . ': ') . $oldProfileName);

        return true;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->_id = (int)$id;
    }

    /**
     * Actualizar un perfil.
     *
     * @return bool
     */
    public function profileUpdate()
    {
        $oldProfileName = static::getProfileNameById($this->getId());

        $query = 'UPDATE usrProfiles SET '
            . 'userprofile_name = :name,'
            . 'userprofile_profile = :profile '
            . 'WHERE userprofile_id = :id LIMIT 1';

        $data['id'] = $this->getId();
        $data['name'] = $this->getName();
        $data['profile'] = serialize($this);

        if (DB::getQuery($query, __FUNCTION__, $data) === false) {
            return false;
        }

        Log::writeNewLogAndEmail(_('Modificar Perfil'), Html::strongText(_('Perfil') . ': ') . $oldProfileName . ' > ' . $this->getName());

        return true;
    }
}