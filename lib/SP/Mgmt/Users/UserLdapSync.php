<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Bootstrap;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserData;
use SP\Log\Log;
use SP\Util\Util;

/**
 * Class UserLdapSync
 *
 * @package SP\Mgmt\Users
 */
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
     * @param array $options
     * @return bool
     */
    public static function run(array &$options)
    {
        $ConfigData = Bootstrap::getContainer()['configData'];

        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Sincronización LDAP', false));

        $Ldap = $ConfigData->isLdapAds() || $options['isADS'] ? new LdapMsAds() : new LdapStd();

        $ldapObjects = $Ldap->findObjects();

        if (!$ldapObjects) {
            return false;
        }

        self::$totalObjects = (int)$ldapObjects['count'];

        $LogMessage->addDetails(__('Objetos encontrados', false), self::$totalObjects);

        if (self::$totalObjects > 0) {
            $UserData = new UserData();

            foreach ($ldapObjects as $result) {
                if (is_array($result)) {
                    $User = clone $UserData;

                    foreach ($result as $attribute => $values) {

                        $value = $values[0];

                        switch (strtolower($attribute)) {
                            case $options['nameAttribute']:
                                $User->setName($value);
                                break;
                            case $options['loginAttribute']:
                                $User->setLogin($value);
                                break;
                            case 'mail':
                                $User->setEmail($value);
                                break;
                        }
                    }

                    if (!empty($User->getName())
                        && !empty($User->getLogin())
                    ) {
                        $User->setPass(Util::generateRandomBytes());

                        try {
                            $LogMessage->addDetails(__('Usuario', false), sprintf('%s (%s)', $User->getName(), $User->getLogin()));
                            UserLdap::getItem($User)->add();

                            self::$syncedObjects++;
                        } catch (SPException $e) {
                            self::$errorObjects++;
                            $LogMessage->addDescription($e->getMessage());
                        }
                    }
                }
            }
        } else {
            $LogMessage->addDescription(__('No se encontraron objetos para sincronizar', false));
            $Log->writeLog();

            return true;
        }

        $LogMessage->addDescription(__('Sincronización finalizada', false));
        $Log->writeLog();

        return true;
    }
}