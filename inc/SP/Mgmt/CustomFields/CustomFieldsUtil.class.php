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
use SP\DataModel\CustomFieldData;
use SP\Log\Log;
use SP\Storage\DB;
use SP\Storage\QueryData;

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
        return (!is_array($fields) || $srcHhash == md5(implode('', $fields)));
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
        $Log = new Log();
        $Log->setAction(_('Campos Personalizados'));

        $query = /** @lang SQL */
            'SELECT customfielddata_id, customfielddata_data, customfielddata_iv FROM customFieldsData';

        $Data = new QueryData();
        $Data->setMapClassName('SP\DataModel\CustomFieldData');
        $Data->setQuery($query);

        $queryRes = DB::getResultsArray($Data);

        if (count($queryRes) === 0) {
            $Log->addDescription(_('Fin'));
            $Log->writeLog();

            return true;
        }

        $Log->addDescription(_('Actualizando datos encriptados'));
        $Log->writeLog(true);

        $errors = array();
        $success = array();

        foreach ($queryRes as $CustomField) {
            /** @var CustomFieldData $CustomField */
            $fieldData = Crypt::getDecrypt($CustomField->getCustomfielddataData(), $CustomField->getCustomfielddataIv(), $currentMasterPass);
            $fieldCryptData = Crypt::encryptData($fieldData, $newMasterPassword);

            $query = /** @lang SQL */
                'UPDATE customFieldsData SET
                customfielddata_data = :data,
                customfielddata_iv = :iv
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

        if (count($errors) > 0) {
            $Log->addDetails(_('Registros no actualizados'), implode(',', $errors));
            $Log->writeLog(true);
        }

        if (count($success) > 0) {
            $Log->addDetails(_('Registros actualizados'), implode(',', $success));
            $Log->writeLog(true);
        }

        $Log->addDescription(_('Fin'));
        $Log->writeLog();

        return (count($errors) === 0);
    }

    /**
     * Crear los campos personalizados de un elemento
     *
     * @param array           $customFields
     * @param CustomFieldData $CustomFieldData
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
     */
    public static function updateItemCustomFields(array $customFields, CustomFieldData $CustomFieldData)
    {
        foreach ($customFields as $id => $value) {
            $CustomFieldData->setDefinitionId($id);
            $CustomFieldData->setValue($value);

            CustomField::getItem($CustomFieldData)->update();
        }
    }
}