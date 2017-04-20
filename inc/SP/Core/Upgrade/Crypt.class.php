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

namespace SP\Core\Upgrade;

use SP\Account\AccountCrypt;
use SP\Account\AccountHistory;
use SP\Account\AccountHistoryCrypt;
use SP\Config\ConfigDB;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\SPException;
use SP\Core\Init;
use SP\Log\Log;
use SP\Mgmt\CustomFields\CustomFieldsUtil;
use SP\Mgmt\Users\UserMigrate;
use SP\Storage\DB;

/**
 * Class Crypt
 *
 * @package SP\Core\Upgrade
 */
class Crypt
{
    /**
     * Migrar elementos encriptados
     *
     * @param $masterPass
     * @return bool
     * @throws \Exception
     */
    public static function migrate(&$masterPass)
    {
        try {
            AccountHistoryCrypt::$currentMPassHash = ConfigDB::getValue('masterPwd');

            if (!DB::beginTransaction()) {
                throw new SPException(SPException::SP_ERROR, __('No es posible iniciar una transacción', false));
            }

            if (!self::migrateHash($masterPass)) {
                throw new SPException(SPException::SP_INFO, __('Clave maestra incorrecta', false));
            }

            self::migrateAccounts($masterPass);
            self::migrateCustomFields($masterPass);
            UserMigrate::setMigrateUsers();

            if (!DB::endTransaction()) {
                throw new SPException(SPException::SP_ERROR, __('No es posible finalizar una transacción', false));
            }

            debugLog('Total time: ' . getElapsedTime());

            return true;
        } catch (\Exception $e) {
            if (DB::rollbackTransaction()) {
                debugLog('Rollback: ' . __METHOD__);
            }

            throw $e;
        }
    }

    /**
     * Migrar el hash de clave maestra
     *
     * @param $masterPass
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function migrateHash(&$masterPass)
    {
        $configHashMPass = ConfigDB::getValue('masterPwd');

        // Comprobar si el hash está en formato anterior a 12002
        if (strlen($configHashMPass) === 128) {
            if (hash('sha256', substr($configHashMPass, 0, 64) . $masterPass) === substr($configHashMPass, 64, 64)) {
                $newHash = Hash::hashKey($masterPass);

                AccountHistory::updateAccountsMPassHash($newHash);

                ConfigDB::setValue('masterPwd', $newHash);
                Log::writeNewLog(__('Aviso', false), __('Se ha regenerado el HASH de clave maestra. No es necesaria ninguna acción.', false), Log::NOTICE);

                return true;
            }

            // Hash de clave maestra anterior a 2.0.0.17013101
            // Hash de clave maestra anterior a 2.0.0.17021601
        } elseif ((substr($configHashMPass, 72) !== false && hash_equals(crypt($masterPass, substr($configHashMPass, 0, 72)), substr($configHashMPass, 72)))
            || (substr($configHashMPass, 30) !== false && hash_equals(crypt($masterPass, substr($configHashMPass, 0, 30)), substr($configHashMPass, 30)))
        ) {
            ConfigDB::setValue('masterPwd', Hash::hashKey($masterPass));
            Log::writeNewLog(__('Aviso', false), __('Se ha regenerado el HASH de clave maestra. No es necesaria ninguna acción.', false), Log::NOTICE);

            return true;
        }

        return Hash::checkHashKey($masterPass, $configHashMPass);
    }

    /**
     * Migrar claves de cuentas a nuevo formato
     *
     * @param $masterPass
     */
    private static function migrateAccounts(&$masterPass)
    {
        $AccountCrypt = new AccountCrypt();
        $AccountCrypt->updateOldPass($masterPass);

        $AccountHistoryCrypt = new AccountHistoryCrypt();
        $AccountHistoryCrypt->updateOldPass($masterPass);
    }

    /**
     * Migrar los datos de los campos personalizados a nuevo formato
     *
     * @param $masterPass
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    private static function migrateCustomFields(&$masterPass)
    {
        CustomFieldsUtil::updateCustomFieldsOldCrypt($masterPass);
    }
}