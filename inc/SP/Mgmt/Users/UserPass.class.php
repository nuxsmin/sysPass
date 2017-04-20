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

use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use SP\Config\Config;
use SP\Config\ConfigDB;
use SP\Controller\LoginController;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Core\Upgrade\User as UpgradeUser;
use SP\DataModel\UserData;
use SP\DataModel\UserLoginData;
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
 * @property UserPassData $itemData
 */
class UserPass extends UserBase
{
    const MPASS_WRONG = 0;

    // La clave maestra incorrecta
    const MPASS_OK = 1;
    // La clave maestra correcta
    const MPASS_NOTSET = 2;
    // La clave maestra no está guardada
    const MPASS_CHANGED = 3;
    // La clave maestra ha cambiado
    const MPASS_CHECKOLD = 4;
    // Comprobar la clave maestra con la calve del usuario anterior
    /**
     * @var bool
     */
    public static $gotMPass = false;
    /**
     * @var string
     */
    private static $clearUserMPass = '';

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
     * @param int $userId ID de usuario
     * @return bool
     */
    public static function checkUserUpdateMPass($userId)
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
        $Data->addParam($userId);

        /** @var UserPassData $queryRes */
        $queryRes = DB::getResults($Data);

        return ($queryRes !== false && $queryRes->getUserLastUpdateMPass() >= $configMPassTime);
    }

    /**
     * Actualizar la clave maestra con la clave anterior del usuario
     *
     * @param string        $oldUserPass
     * @param UserLoginData $UserData
     * @return bool
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public static function updateMasterPassFromOldPass($oldUserPass, UserLoginData $UserData)
    {
        if (self::loadUserMPass($UserData, $oldUserPass) === UserPass::MPASS_OK) {
            return self::updateUserMPass(self::$clearUserMPass, $UserData);
        }

        return UserPass::MPASS_WRONG;
    }

    /**
     * Comprueba la clave maestra del usuario.
     *
     * @param UserLoginData $UserData
     * @param string        $key Clave de cifrado
     * @return bool
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public static function loadUserMPass(UserLoginData $UserData, $key = null)
    {
        $configHashMPass = ConfigDB::getValue('masterPwd');

        if (empty($configHashMPass)
            || empty($UserData->getUserMPass())
            || empty($UserData->getUserMKey())
        ) {
            return self::MPASS_NOTSET;
        }

        if ($UserData->getUserLastUpdateMPass() < ConfigDB::getValue('lastupdatempass')) {
            return self::MPASS_CHANGED;
        }

        if ($UserData->isUserIsMigrate() === 1) {
            return UpgradeUser::upgradeMasterKey($UserData) ? self::MPASS_OK : self::MPASS_WRONG;
        }

        if ($key === null && $UserData->isUserIsChangedPass() === 1
        ) {
            return self::MPASS_CHECKOLD;
        }

        try {
            $securedKey = Crypt::unlockSecuredKey($UserData->getUserMKey(), self::getKey($UserData, $key));
            $userMPass = Crypt::decrypt($UserData->getUserMPass(), $securedKey, self::getKey($UserData, $key));

            // Comprobamos el hash de la clave del usuario con la guardada
            if (Hash::checkHashKey($userMPass, $configHashMPass)) {
                self::$gotMPass = true;
                self::$clearUserMPass = $userMPass;

                CryptSession::saveSessionKey($userMPass);

                return self::MPASS_OK;
            }
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            return self::MPASS_CHECKOLD;
        }

        return self::MPASS_WRONG;
    }

    /**
     * Obtener una clave de cifrado basada en la clave del usuario y un salt.
     *
     * @param UserLoginData $UserData
     * @param string        $key Clave de cifrado
     * @return string con la clave de cifrado
     */
    private static function getKey(UserLoginData $UserData, $key = null)
    {
        $pass = $key === null ? $UserData->getLoginPass() : $key;

        return $pass . $UserData->getLogin() . Config::getConfig()->getPasswordSalt();
    }

    /**
     * Actualizar la clave maestra del usuario en la BBDD.
     *
     * @param string                 $userMPass con la clave maestra
     * @param UserData|UserLoginData $UserData  $UserData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Exceptions\SPException
     * @throws QueryException
     */
    public static function updateUserMPass($userMPass, UserLoginData $UserData)
    {
        $configHashMPass = ConfigDB::getValue('masterPwd');

        if ($configHashMPass === false) {
            return self::MPASS_NOTSET;
        }

        if (null === $configHashMPass) {
            $configHashMPass = Hash::hashKey($userMPass);
            ConfigDB::setValue('masterPwd', $configHashMPass);
        }

        if (Hash::checkHashKey($userMPass, $configHashMPass)
            || \SP\Core\Upgrade\Crypt::migrateHash($userMPass)
        ) {
            $securedKey = Crypt::makeSecuredKey(self::getKey($UserData));
            $cryptMPass = Crypt::encrypt($userMPass, $securedKey, self::getKey($UserData));

            if (!empty($cryptMPass)) {
                if (strlen($securedKey) > 1000 || strlen($cryptMPass) > 1000) {
                    throw new QueryException(SPException::SP_ERROR, __('Error interno', false), '', LoginController::STATUS_INTERNAL_ERROR);
                }

                $query = /** @lang SQL */
                    'UPDATE usrData SET 
                    user_mPass = ?,
                    user_mKey = ?,
                    user_lastUpdateMPass = UNIX_TIMESTAMP(),
                    user_isMigrate = 0,
                    user_isChangedPass = 0 
                    WHERE user_id = ? LIMIT 1';

                $Data = new QueryData();
                $Data->setQuery($query);
                $Data->addParam($cryptMPass);
                $Data->addParam($securedKey);
                $Data->addParam($UserData->getUserId());

                self::$clearUserMPass = $userMPass;
                self::$gotMPass = true;

                CryptSession::saveSessionKey($userMPass);

                $UserData->setUserMPass($cryptMPass);
                $UserData->setUserMKey($securedKey);

                DB::getQuery($Data);

                return self::MPASS_OK;
            }
        }

        return self::MPASS_WRONG;
    }

    /**
     * @return string
     */
    public static function getClearUserMPass()
    {
        return self::$clearUserMPass;
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
     * Inicializar la clase
     *
     * @return void
     * @throws InvalidClassException
     */
    protected function init()
    {
        $this->setDataModel(UserPassData::class);
    }
}