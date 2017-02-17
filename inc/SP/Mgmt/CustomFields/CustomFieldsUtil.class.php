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

namespace SP\Mgmt\CustomFields;

defined('APP_ROOT') || die();

use SP\Core\Crypt\Crypt;
use SP\Core\OldCrypt;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldData;
use SP\DataModel\CustomFieldDefData;
use SP\Log\Log;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Class CustomFieldsUtil utilidades para los campos personalizados
 *
 * @package SP\Mgmt
 */
class CustomFieldsUtil
{
    /**
     * Comprobar si el hash de cambios coincide con el camculado con el valor de los campos del elemento
     *
     * @param $fields
     * @param $srcHhash
     * @return bool
     */
    public static function checkHash(&$fields, $srcHhash)
    {
        return (!is_array($fields) || $srcHhash === md5(implode('', $fields)));
    }

    /**
     * Actualizar los datos encriptados con una nueva clave
     *
     * @param string $currentMasterPass La clave maestra actual
     * @param string $newMasterPassword La nueva clave maestra
     * @return bool
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function updateCustomFieldsCrypt($currentMasterPass, $newMasterPassword)
    {
        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Campos Personalizados', false));

        $query = /** @lang SQL */
            'SELECT customfielddata_id, customfielddata_data, customfielddata_iv FROM customFieldsData';

        $Data = new QueryData();
        $Data->setMapClassName(CustomFieldData::class);
        $Data->setQuery($query);

        /** @var CustomFieldData[] $queryRes */
        $queryRes = DB::getResultsArray($Data);

        if (count($queryRes) === 0) {
            $LogMessage->addDescription(__('No hay datos de campos personalizados', false));
            $Log->writeLog();
            return true;
        }

        $LogMessage->addDescription(__('Actualizando datos encriptados', false));

        $errors = [];
        $success = [];

        foreach ($queryRes as $CustomField) {
            $currentSecuredKey = Crypt::unlockSecuredKey($CustomField->getCustomfielddataIv(), $currentMasterPass);
            $fieldData = Crypt::decrypt($CustomField->getCustomfielddataData(), $currentSecuredKey);

            $securedKey = Crypt::makeSecuredKey($newMasterPassword);

            $query = /** @lang SQL */
                'UPDATE customFieldsData SET
                customfielddata_data = ?,
                customfielddata_iv = ? 
                WHERE customfielddata_id = ?';

            $Data = new QueryData();
            $Data->setQuery($query);
            $Data->addParam(Crypt::encrypt($fieldData, $securedKey));
            $Data->addParam($securedKey);
            $Data->addParam($CustomField->getCustomfielddataId());

            try {
                DB::getQuery($Data);

                $success[] = $CustomField->getCustomfielddataId();
            } catch (SPException $e) {
                $errors[] = $CustomField->getCustomfielddataId();
            }
        }

        $LogMessage->addDetails(__('Registros no actualizados', false), implode(',', $errors));
        $LogMessage->addDetails(__('Registros actualizados', false), implode(',', $success));
        $Log->writeLog();

        return (count($errors) === 0);
    }

    /**
     * Actualizar los datos encriptados con una nueva clave
     *
     * @param string $currentMasterPass La clave maestra actual
     * @return bool
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function updateCustomFieldsOldCrypt(&$currentMasterPass)
    {
        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Campos Personalizados', false));

        $query = /** @lang SQL */
            'SELECT customfielddata_id, customfielddata_data, customfielddata_iv FROM customFieldsData';

        $Data = new QueryData();
        $Data->setMapClassName(CustomFieldData::class);
        $Data->setQuery($query);

        /** @var CustomFieldData[] $queryRes */
        $queryRes = DB::getResultsArray($Data);

        if (count($queryRes) === 0) {
            $LogMessage->addDescription(__('No hay datos de campos personalizados', false));
            $Log->writeLog();
            return true;
        }

        $LogMessage->addDescription(__('Actualizando datos encriptados', false));

        $errors = [];
        $success = [];

        foreach ($queryRes as $CustomField) {
            $securedKey = Crypt::makeSecuredKey($currentMasterPass);
            $fieldData = OldCrypt::getDecrypt($CustomField->getCustomfielddataData(), $CustomField->getCustomfielddataIv(), $currentMasterPass);

            $query = /** @lang SQL */
                'UPDATE customFieldsData SET
                customfielddata_data = ?,
                customfielddata_iv = ? 
                WHERE customfielddata_id = ?';

            $Data = new QueryData();
            $Data->setQuery($query);
            $Data->addParam(Crypt::encrypt($fieldData, $securedKey));
            $Data->addParam($securedKey);
            $Data->addParam($CustomField->getCustomfielddataId());

            try {
                DB::getQuery($Data);

                $success[] = $CustomField->getCustomfielddataId();
            } catch (SPException $e) {
                $errors[] = $CustomField->getCustomfielddataId();
            }
        }

        $LogMessage->addDetails(__('Registros no actualizados', false), implode(',', $errors));
        $LogMessage->addDetails(__('Registros actualizados', false), implode(',', $success));
        $Log->writeLog();

        return (count($errors) === 0);
    }

    /**
     * Crear los campos personalizados de un elemento
     *
     * @param array           $customFields
     * @param CustomFieldData $CustomFieldData
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function addItemCustomFields(array &$customFields, CustomFieldData $CustomFieldData)
    {
        foreach ($customFields as $id => $value) {
            $CustomFieldData->setDefinitionId($id);
            $CustomFieldData->setValue($value);

            CustomField::getItem($CustomFieldData)->add();
        }
    }

    /**
     * Actualizar los campos personalizados de un elemento
     *
     * @param array           $customFields
     * @param CustomFieldData $CustomFieldData
     * @throws \SP\Core\Exceptions\InvalidClassException
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function updateItemCustomFields(array $customFields, CustomFieldData $CustomFieldData)
    {
        foreach ($customFields as $id => $value) {
            $CustomFieldData->setDefinitionId($id);
            $CustomFieldData->setValue($value);

            CustomField::getItem($CustomFieldData)->update();
        }
    }

    /**
     * Migración de campos personalizados
     *
     * @return bool
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public static function migrateCustomFields()
    {
        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__FUNCTION__);

        $query = /** @lang SQL */
            'SELECT DISTINCT customfielddef_id, customfielddef_field
            FROM customFieldsData 
            LEFT JOIN customFieldsDef ON customfielddef_id = customfielddata_defId
            WHERE customfielddata_moduleId = 20';

        $Data = new QueryData();
        $Data->setQuery($query);

        /** @var CustomFieldDefData[] $oldDefs */
        $oldDefs = DB::getResultsArray($Data);

        try {
            if (count($oldDefs) > 0) {
                $query = /** @lang SQL */
                    'UPDATE customFieldsData SET customfielddata_moduleId = 10 WHERE customfielddata_moduleId = 20';

                $Data = new QueryData();
                $Data->setQuery($query);
                $Data->setOnErrorMessage(__('Error al migrar campos personalizados', false));

                DB::getQuery($Data);

                $query = /** @lang SQL */
                    'UPDATE customFieldsDef SET
                        customfielddef_module = ?,
                        customfielddef_field = ?
                        WHERE customfielddef_id= ? LIMIT 1';

                foreach ($oldDefs as $cf) {
                    $CustomFieldDef = Util::castToClass(CustomFieldDefData::class, $cf->customfielddef_field);
                    $CustomFieldDef->setId($cf->customfielddef_id);
                    $CustomFieldDef->setModule(10);
                    $CustomFieldDef->setCustomfielddefModule(10);

                    $Data = new QueryData();
                    $Data->setQuery($query);
                    $Data->addParam(10);
                    $Data->addParam(serialize($CustomFieldDef));
                    $Data->addParam($cf->customfielddef_id);

                    try {
                        DB::getQuery($Data);

                        $LogMessage->addDetails(__('Campo actualizado', false), $cf->customfielddef_id);
                    } catch (SPException $e) {
                        $LogMessage->addDetails(__('Error al actualizar el campo personalizado', false), $cf->customfielddef_id);
                    }
                }
            }

            $Log->writeLog();

            return true;
        } catch (SPException $e) {
            $LogMessage->addDescription($e->getMessage());
            $LogMessage->addDescription($e->getHint());
            $Log->setLogLevel(Log::ERROR);
            $Log->writeLog();
        }

        // We are here...wrong
        return false;
    }
}