<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Mgmt\CustomFields;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\Crypt;
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
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function updateCustomFieldsCrypt($currentMasterPass, $newMasterPassword)
    {
        $Log = new Log(_('Campos Personalizados'));

        $query = /** @lang SQL */
            'SELECT customfielddata_id, customfielddata_data, customfielddata_iv FROM customFieldsData';

        $Data = new QueryData();
        $Data->setMapClassName(CustomFieldData::class);
        $Data->setQuery($query);

        /** @var CustomFieldData[] $queryRes */
        $queryRes = DB::getResultsArray($Data);

        if (count($queryRes) === 0) {
            $Log->addDescription(_('No hay datos de campos personalizados'));
            $Log->writeLog();
            return true;
        }

        $Log->addDescription(_('Actualizando datos encriptados'));

        $errors = [];
        $success = [];

        foreach ($queryRes as $CustomField) {
            $fieldData = Crypt::getDecrypt($CustomField->getCustomfielddataData(), $CustomField->getCustomfielddataIv(), $currentMasterPass);
            $fieldCryptData = Crypt::encryptData($fieldData, $newMasterPassword);

            $query = /** @lang SQL */
                'UPDATE customFieldsData SET
                customfielddata_data = ?,
                customfielddata_iv = ? 
                WHERE customfielddata_id = ?';

            $Data = new QueryData();
            $Data->setQuery($query);
            $Data->addParam($fieldCryptData['data']);
            $Data->addParam($fieldCryptData['iv']);
            $Data->addParam($CustomField->getCustomfielddataId());

            if (DB::getQuery($Data) === false) {
                $errors[] = $CustomField->getCustomfielddataId();
            } else {
                $success[] = $CustomField->getCustomfielddataId();
            }
        }

        $Log->addDetails(_('Registros no actualizados'), implode(',', $errors));
        $Log->addDetails(_('Registros actualizados'), implode(',', $success));
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
        $Log = new Log(__FUNCTION__);

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

                if (DB::getQuery($Data) === false) {
                    throw new SPException(SPException::SP_ERROR, _('Error al migrar campos personalizados'));
                }

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

                    if (DB::getQuery($Data) === false) {
                        $Log->addDetails(_('Error al actualizar el campo personalizado'), $cf->customfielddef_id);
                    } else {
                        $Log->addDetails(_('Campo actualizado'), $cf->customfielddef_id);
                    }
                }
            }

            return true;
        } catch (SPException $e) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription($e->getMessage());
            $Log->addDescription($e->getHint());
        }

        // We are here...wrong
        return false;
    }
}