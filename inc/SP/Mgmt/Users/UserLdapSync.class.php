<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

use SP\Auth\Ldap\LdapMsAds;
use SP\Auth\Ldap\LdapStd;
use SP\Config\Config;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserData;
use SP\Log\Log;
use SP\Util\Util;

class UserLdapSync
{
    /**
     * @var int
     */
    public static $totalObjects = 0;
    /**
     * @var int
     */
    public static $syncedObjects = 0;
    /**
     * @var int
     */
    public static $errorObjects = 0;

    /**
     * Sincronizar usuarios de LDAP
     *
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public static function run()
    {
        $Log = new Log(__('Sincronización LDAP', false));

        $Ldap = Config::getConfig()->isLdapAds() ? new LdapMsAds() : new LdapStd();

        $ldapObjects = $Ldap->findObjects();
        self::$totalObjects = (int)$ldapObjects['count'];

        $Log->addDescription(sprintf(__('Objetos encontrados: %d', false), self::$totalObjects));

        if (self::$totalObjects > 0) {
            $UserData = new UserData();

            foreach ($ldapObjects as $result) {
                $User = clone $UserData;

                if (is_array($result)) {
                    foreach ($result as $attribute => $values) {

                        $value = $values[0];

                        switch (strtolower($attribute)) {
                            case 'displayname':
                            case 'fullname':
                                $User->setUserName($value);
                                break;
                            case 'login':
                            case 'samaccountname':
                            case 'uid':
                                $User->setUserLogin(strtolower($value));
                                break;
                            case 'mail':
                                $User->setUserEmail(strtolower($value));
                                break;
                        }
                    }

                    $User->setUserPass(Util::generateRandomBytes());

                    try {
                        $Log->addDescription(sprintf(__('Creando usuario \'%s (%s)\'', false), $User->getUserName(), $User->getUserLogin()));
                        UserLdap::getItem($User)->add();

                        self::$syncedObjects++;
                    } catch (SPException $e) {
                        self::$errorObjects++;
                        $Log->addDescription($e->getMessage());
                    }
                }
            }
        } else {
            $Log->addDescription(__('No se encontraron objetos para sincronizar', false));
            $Log->writeLog();

            return false;
        }

        $Log->addDescription(__('Sincronización finalizada', false));
        $Log->writeLog();

        return true;
    }
}