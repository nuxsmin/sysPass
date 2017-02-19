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

namespace SP\Mgmt\Users;

defined('APP_ROOT') || die();

use SP\Config\Config;
use SP\Config\ConfigDB;
use SP\Controller\LoginController;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Core\Upgrade\User;
use SP\DataModel\UserPassData;
use SP\Log\Email;
use SP\Log\Log;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Core\Crypt\Session as CryptSession;

/**
 * Class UserPass para la gestión de las claves de un usuario
 *
 * @package SP
 */
class UserPass extends UserBase
{
    /**
     * @var string
     */
    protected $clearUserMPass = '';

    /**
     * Category constructor.
     *
     * @param UserPassData $itemData
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function __construct($itemData = null)
    {
        $this->setDataModel(UserPassData::class);

        parent::__construct($itemData);
    }

    /**
     * Obtener el IV del usuario a partir del Id.
     *
     * @param int $id El id del usuario
     * @return string El hash
     */
    public static function getUserIVById($id)
    {
        $query = /** @lang SQL */
            'SELECT user_mKey FROM usrData WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->user_mKey;
    }

    /**
     * Comprobar si el usuario tiene actualizada la clave maestra actual.
     *
     * @return bool
     */
    public function checkUserUpdateMPass()
    {
        $configMPassTime = ConfigDB::getValue('lastupdatempass');

        if (empty($configMPassTime)) {
            return false;
        }

        $query = /** @lang SQL */
            'SELECT user_lastUpdateMPass FROM usrData WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName(UserPassData::class);
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserId());

        /** @var UserPassData $queryRes */
        $queryRes = DB::getResults($Data);

        return ($queryRes !== false && $queryRes->getUserLastUpdateMPass() >= $configMPassTime);
    }

    /**
     * Modificar la clave de un usuario.
     *
     * @param $userId
     * @param $userPass
     * @return $this
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \phpmailer\phpmailerException
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateUserPass($userId, $userPass)
    {
        $this->setItemData(User::getItem()->getById($userId));

        $query = /** @lang SQL */
            'UPDATE usrData SET
            user_pass = ?,
            user_hashSalt = \'\',
            user_isChangePass = 0,
            user_lastUpdate = NOW() 
            WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(Hash::hashKey($userPass));
        $Data->addParam($userId);
        $Data->setOnErrorMessage(__('Error al modificar la clave', false));

        DB::getQuery($Data);

        $Log = new Log();
        $Log->getLogMessage()
            ->setAction(__('Modificar Clave Usuario', false))
            ->addDetails(__('Login', false), $this->itemData->getUserLogin());
        $Log->writeLog();

        Email::sendEmail($Log->getLogMessage());

        return $this;
    }

    /**
     * Comprueba la clave maestra del usuario.
     *
     * @return bool
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function loadUserMPass()
    {
        $userMPass = $this->getUserMPass();
        $configHashMPass = ConfigDB::getValue('masterPwd');

        if ($userMPass === false || empty($configHashMPass)) {
            return false;

            // Comprobamos el hash de la clave del usuario con la guardada
        } elseif (Hash::checkHashKey($userMPass, $configHashMPass)) {
            $this->clearUserMPass = $userMPass;

            CryptSession::saveSessionKey($userMPass);

            return true;
        }

        return null;
    }

    /**
     * Desencriptar la clave maestra del usuario para la sesión.
     *
     * @param string $key Clave de cifrado
     * @return false|string Devuelve bool se hay error o string si se devuelve la clave
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public function getUserMPass($key = null)
    {
        $query = /** @lang SQL */
            'SELECT user_mPass,
            user_mKey,
            user_lastUpdateMPass,
            BIN(user_isMigrate) AS user_isMigrate 
            FROM usrData WHERE user_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getUserId());

        $queryRes = DB::getResults($Data);

        if ($queryRes === false
            || empty($queryRes->user_mPass)
            || empty($queryRes->user_mKey)
            || $queryRes->user_lastUpdateMPass < ConfigDB::getValue('lastupdatempass')
        ) {
            return false;
        } elseif ((int)$queryRes->user_isMigrate === 1) {
            $this->itemData->setUserMPass($queryRes->user_mPass);
            $this->itemData->setUserMKey($queryRes->user_mKey);

            return User::upgradeMasterKey($this);
        }

        $this->itemData->setUserMPass($queryRes->user_mPass);
        $this->itemData->setUserMKey($queryRes->user_mKey);

        $securedKey = Crypt::unlockSecuredKey($queryRes->user_mKey, $this->getKey($key));

        return Crypt::decrypt($queryRes->user_mPass, $securedKey, $this->getKey($key));
    }

    /**
     * Obtener una clave de cifrado basada en la clave del usuario y un salt.
     *
     * @param string $key Clave de cifrado
     * @return string con la clave de cifrado
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    private function getKey($key = null)
    {
        $pass = $key === null ? $this->itemData->getUserPass() : $key;

        return $pass . $this->itemData->getUserLogin() . Config::getConfig()->getPasswordSalt();
    }

    /**
     * @return string
     */
    public function getClearUserMPass()
    {
        return $this->clearUserMPass;
    }

    /**
     * Actualizar la clave maestra con la clave anterior del usuario
     *
     * @param $oldUserPass
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateMasterPass($oldUserPass)
    {
        $masterPass = $this->getUserMPass($oldUserPass);

        if ($masterPass) {
            return $this->updateUserMPass($masterPass);
        }

        return false;
    }

    /**
     * Actualizar la clave maestra del usuario en la BBDD.
     *
     * @param string $masterPwd con la clave maestra
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function updateUserMPass($masterPwd)
    {
        $configHashMPass = ConfigDB::getValue('masterPwd');

        if ($configHashMPass === false) {
            return false;
        } elseif (null === $configHashMPass) {
            $configHashMPass = Hash::hashKey($masterPwd);
            ConfigDB::setValue('masterPwd', $configHashMPass);
        }

        if (Hash::checkHashKey($masterPwd, $configHashMPass)
            || \SP\Core\Upgrade\Crypt::migrateHash($masterPwd)
        ) {
            $securedKey = Crypt::makeSecuredKey($this->getKey());
            $cryptMPass = Crypt::encrypt($masterPwd, $securedKey, $this->getKey());

            if (!empty($cryptMPass)) {
                if (strlen($securedKey) > 1000 || strlen($cryptMPass) > 1000) {
                    throw new QueryException(SPException::SP_ERROR, __('Error interno', false), '', LoginController::STATUS_INTERNAL_ERROR);
                }

                $query = /** @lang SQL */
                    'UPDATE usrData SET 
                    user_mPass = ?,
                    user_mKey = ?,
                    user_lastUpdateMPass = UNIX_TIMESTAMP(),
                    user_isMigrate = 0
                    WHERE user_id = ? LIMIT 1';

                $Data = new QueryData();
                $Data->setQuery($query);
                $Data->addParam($cryptMPass);
                $Data->addParam($securedKey);
                $Data->addParam($this->itemData->getUserId());

                $this->clearUserMPass = $masterPwd;

                $this->itemData->setUserMPass($cryptMPass);
                $this->itemData->setUserMKey($securedKey);

                DB::getQuery($Data);

                return true;
            }
        }

        return false;
    }
}