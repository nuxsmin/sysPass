<?php

/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar las operaciones actualización de la aplicación.
 */
class SP_Upgrade {

    private static $result = array();
    private static $upgrade = array(110, 1121, 1122);

    /**
     * @brief Inicia el proceso de actualización de la BBDD
     * @param int $version con la versión de la BBDD actual
     * @returns bool
     */
    public static function doUpgrade($version) {
        foreach (self::$upgrade as $upgradeVersion) {
            if ($version < $upgradeVersion) {
                error_log($upgradeVersion);
                
                if (self::upgradeTo($upgradeVersion) === FALSE) {
                    SP_Init::initError(
                            _('Error al aplicar la actualización de la Base de Datos'),
                            _('Compruebe el registro de eventos para más detalles') . '. <a href="index.php?nodbupgrade=1">' . _('Acceder') . '</a>');
                }
            }
        }

        return TRUE;
    }

    /**
     * @brief Comprueba si es necesario actualizar la BBDD
     * @returns bool
     */
    public static function needUpgrade($version) {
        return ( in_array($version, self::$upgrade) );
    }

    /**
     * @brief Actualiza la BBDD según la versión
     * @param int $version con la versión a actualizar
     * @returns bool
     */
    private static function upgradeTo($version) {
        $result['action'] = _('Actualizar BBDD');

        switch ($version) {
            case 110:
                $queries[] = "ALTER TABLE `accFiles` CHANGE COLUMN `accfile_name` `accfile_name` VARCHAR(100) NOT NULL";
                $queries[] = "ALTER TABLE `accounts` ADD COLUMN `account_otherGroupEdit` BIT(1) NULL DEFAULT 0 AFTER `account_dateEdit`, ADD COLUMN `account_otherUserEdit` BIT(1) NULL DEFAULT 0 AFTER `account_otherGroupEdit`;";
                $queries[] = "CREATE TABLE `accUsers` (`accuser_id` INT NOT NULL AUTO_INCREMENT,`accuser_accountId` INT(10) UNSIGNED NOT NULL,`accuser_userId` INT(10) UNSIGNED NOT NULL, PRIMARY KEY (`accuser_id`), INDEX `idx_account` (`accuser_accountId` ASC));";
                $queries[] = "ALTER TABLE `accHistory` ADD COLUMN `accHistory_otherUserEdit` BIT NULL AFTER `acchistory_mPassHash`, ADD COLUMN `accHistory_otherGroupEdit` VARCHAR(45) NULL AFTER `accHistory_otherUserEdit`;";
                $queries[] = "ALTER TABLE `accFiles` CHANGE COLUMN `accfile_type` `accfile_type` VARCHAR(100) NOT NULL ;";
                break;
            case 1121:
                $queries[] = "ALTER TABLE `categories` ADD COLUMN `category_description` VARCHAR(255) NULL AFTER `category_name`;";
                $queries[] = "ALTER TABLE `usrProfiles` ADD COLUMN `userProfile_pAppMgmtMenu` BIT(1) NULL DEFAULT b'0' AFTER `userProfile_pUsersMenu`,CHANGE COLUMN `userProfile_pConfigCategories` `userProfile_pAppMgmtCategories` BIT(1) NULL DEFAULT b'0' AFTER `userProfile_pAppMgmtMenu`,ADD COLUMN `userProfile_pAppMgmtCustomers` BIT(1) NULL DEFAULT b'0' AFTER `userProfile_pAppMgmtCategories`;";
                break;
            case 1122:
                $queries[] = "ALTER TABLE `usrData` CHANGE COLUMN `user_login` `user_login` VARCHAR(50) NOT NULL ,CHANGE COLUMN `user_email` `user_email` VARCHAR(80) NULL DEFAULT NULL ;";
                break;
            default :
                $result['text'][] = _('No es necesario actualizar la Base de Datos.');
                return TRUE;
        }

        foreach ($queries as $query) {
            if (DB::doQuery($query, __FUNCTION__) === FALSE && DB::$numError != 1060 && DB::$numError != 1050) {
                $result['text'][] = _('Error al aplicar la actualización de la Base de Datos.') . ' (v' . $version . ')';
                $result['text'][] = 'ERROR: '.DB::$txtError.' ('.DB::$numError.')';
                SP_Common::wrLogInfo($result);
                return FALSE;
            }
        }

        $result['text'][] = _('Actualización de la Base de Datos realizada correctamente.') . ' (v' . $version . ')';
        SP_Common::wrLogInfo($result);
        
        return TRUE;
    }
}