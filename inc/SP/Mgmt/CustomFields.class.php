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

namespace SP\Mgmt;

use SP\Core\Crypt;
use SP\Storage\DB;
use SP\Log\Log;
use SP\Core\SPException;
use SP\Util\Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class CustomFields para la gestión de campos personalizados de los módulos
 *
 * @package SP
 */
class CustomFields extends CustomFieldsBase
{
    /**
     * @var string
     */
    private $_value = '';
    /**
     * @var int
     */
    private $_itemId = 0;

    /**
     * @param $customFieldDefId
     * @param $itemId
     * @param $value
     */
    public function __construct($customFieldDefId, $itemId, $value = null)
    {
        if (is_null($value) || !$itemId || !$customFieldDefId) {
            throw new \InvalidArgumentException(_('Parámetros incorrectos'));
        }

        $fieldDef = CustomFieldDef::getCustomFields($customFieldDefId, true);
        $field = unserialize($fieldDef->customfielddef_field);

        if (get_class($field) === '__PHP_Incomplete_Class') {
            $field = Util::castToClass('SP\Mgmt\CustomFieldDef', $field);
        }

        $this->_id = $customFieldDefId;
        $this->_module = $fieldDef->customfielddef_module;
        $this->_name = $field->getName();
        $this->_type = $field->getType();
        $this->_itemId = $itemId;
        $this->_value = $value;
    }

    /**
     * Devolver los campos personalizados del módulo
     *
     * @param $moduleId int El Id del módulo
     * @return array
     */
    public static function getCustomFieldsForModule($moduleId)
    {
        $query = 'SELECT customfielddef_id, ' .
            'customfielddef_field ' .
            'FROM customFieldsDef ' .
            'WHERE customfielddef_module = :module';

        $data['module'] = $moduleId;

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return array();
        }

        $customFields = array();

        foreach ($queryRes as $customField) {
            /**
             * @var CustomFieldDef $field
             */
            $field = unserialize($customField->customfielddef_field);

            if (get_class($field) === '__PHP_Incomplete_Class') {
                $field = Util::castToClass('SP\Mgmt\CustomFieldDef', $field);
            }

            $attribs = new \stdClass();
            $attribs->id = $customField->customfielddef_id;
            $attribs->name = 'cf_' . strtolower(self::cleanFieldName($field->getName()));
            $attribs->text = $field->getName();
            $attribs->type = $field->getType();
            $attribs->typeName = self::getFieldsTypes($field->getType());
            $attribs->value = '';
            $attribs->help = $field->getHelp();
            $attribs->required = $field->isRequired();

            $customFields[] = $attribs;
        }

        return $customFields;
    }

    private static function cleanFieldName($name)
    {
        return preg_replace('/\W*/', '', $name);
    }

    /**
     * Devuelve los datos de los campos personalizados de un elemento
     *
     * @param $moduleId int El id del módulo
     * @param $itemId   int EL id del elemento
     * @return array
     */
    public static function getCustomFieldsData($moduleId, $itemId)
    {
        $query = 'SELECT customfielddata_id, ' .
            'customfielddef_id, ' .
            'customfielddata_data, ' .
            'customfielddata_iv, ' .
            'customfielddef_field ' .
            'FROM customFieldsData ' .
            'JOIN customFieldsDef ON customfielddata_defId = customfielddef_id ' .
            'WHERE customfielddef_module = :moduleid ' .
            'AND customfielddata_itemId = :itemid ' .
            'ORDER BY customfielddef_id';

        $data['moduleid'] = $moduleId;
        $data['itemid'] = $itemId;

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return array();
        }

        $queryMerge = array_merge($queryRes, self::getCustomFieldsNoData($moduleId, $itemId));

        $customFields = array();

        foreach ($queryMerge as $customField) {
            /**
             * @var CustomFieldDef $field
             */
            $field = unserialize($customField->customfielddef_field);

            if (get_class($field) === '__PHP_Incomplete_Class') {
                $field = Util::castToClass('SP\Mgmt\CustomFieldDef', $field);
            }

            $attribs = new \stdClass();
            $attribs->id = $customField->customfielddef_id;
            $attribs->name = 'cf_' . strtolower(self::cleanFieldName($field->getName()));
            $attribs->text = $field->getName();
            $attribs->type = $field->getType();
            $attribs->typeName = self::getFieldsTypes($field->getType());
            $attribs->value = (isset($customField->customfielddata_data)) ? self::formatValue(Crypt::getDecrypt($customField->customfielddata_data, $customField->customfielddata_iv)) : '';
            $attribs->help = $field->getHelp();
            $attribs->required = $field->isRequired();

            $customFields[] = $attribs;
        }

        return $customFields;
    }

    /**
     * Devuelve los campos personalizados sin datos de un elemento
     *
     * @param $moduleId int El id del módulo
     * @param $itemId   int EL id del elemento
     * @return array
     */
    private static function getCustomFieldsNoData($moduleId, $itemId)
    {
        $query = 'SELECT customfielddef_field,' .
            'customfielddef_id ' .
            'FROM customFieldsDef ' .
            'WHERE customfielddef_module = :moduleidA ' .
            'AND customfielddef_id NOT IN ' .
            '(SELECT customfielddef_id ' .
            'FROM customFieldsData ' .
            'JOIN customFieldsDef ON customfielddata_defId = customfielddef_id ' .
            'WHERE customfielddef_module = :moduleidB ' .
            'AND customfielddata_itemId = :itemid) ' .
            'ORDER BY customfielddef_id';

        $data['moduleidA'] = $moduleId;
        $data['moduleidB'] = $moduleId;
        $data['itemid'] = $itemId;

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__, $data);

        if ($queryRes === false) {
            return array();
        }

        return $queryRes;
    }

    /**
     * Formatear el valor del campo
     *
     * @param $value string El valor del campo
     * @return string
     */
    private static function formatValue($value)
    {
        if (preg_match('#https?://#', $value)) {
            return '<a href="' . $value . '" target="_blank">' . $value . '</a>';
        }

        return $value;
    }

    /**
     * Actualiza el módulo de un campo personalizado
     *
     * @param $moduleId int El Id del módulo nuevo
     * @param $defId    int La definición del campo
     * @return bool
     */
    public static function updateCustomFieldModule($moduleId, $defId)
    {
        $query = 'UPDATE customFieldsData SET ' .
            'customfielddata_moduleId = :moduleid ' .
            'WHERE customfielddata_defId = :defid';

        $data['moduleid'] = $moduleId;
        $data['defid'] = $defId;

        $queryRes = DB::getQuery($query, __FUNCTION__, $data);

        return $queryRes;
    }

    /**
     * Eliminar los datos de un campo personalizado o los de una definición de campos
     *
     * @param int $defId El Id de la definición de campos
     * @return bool
     */
    public static function deleteCustomFieldForDefinition($defId)
    {
        $query = 'DELETE FROM customFieldsData WHERE customfielddata_defId = :defid';
        $data['defid'] = $defId;

        $queryRes = DB::getQuery($query, __FUNCTION__, $data);

        return $queryRes;
    }

    /**
     * Actualizar los datos encriptados con una nueva clave
     *
     * @param string $currentMasterPass La clave maestra actual
     * @param string $newMasterPassword La nueva clave maestra
     * @return bool
     * @throws SPException
     */
    public static function updateCustomFieldsCrypt($currentMasterPass, $newMasterPassword)
    {
        $Log = new Log();
        $Log->setAction(_('Campos Personalizados'));

        $query = 'SELECT customfielddata_id, customfielddata_data, customfielddata_iv FROM customFieldsData';

        DB::setReturnArray();

        $queryRes = DB::getResults($query, __FUNCTION__);

        if ($queryRes === false) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription(_('Error al actualizar datos encriptados'));
            return false;
        }

        $Log->addDescription(_('Actualizando datos encriptados'));
        $Log->writeLog(true);

        $errors = array();
        $success = array();

        foreach ($queryRes as $customField) {
            $fieldData = Crypt::getDecrypt($customField->customfielddata_data, $customField->customfielddata_iv, $currentMasterPass);
            $fieldCryptData = Crypt::encryptData($fieldData, $newMasterPassword);

            $query = 'UPDATE customFieldsData SET ' .
                'customfielddata_data = :data, ' .
                'customfielddata_iv = :iv ' .
                'WHERE customfielddata_id = :id ';

            $data['id'] = $customField->customfielddata_id;
            $data['data'] = $fieldCryptData['data'];
            $data['iv'] = $fieldCryptData['iv'];

            if (DB::getQuery($query, __FUNCTION__, $data) === false) {
                $errors[] = $customField->customfielddata_id;
            } else {
                $success[] = $customField->customfielddata_id;
            }
        }

        if (count($errors) > 0) {
            $Log->addDetails(_('Registros no actualizados'),implode(',', $errors));
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
     * @return int
     */
    public function getItemId()
    {
        return $this->_itemId;
    }

    /**
     * @param int $itemId
     */
    public function setItemId($itemId)
    {
        $this->_itemId = $itemId;
    }

    /**
     * Actualiza los datos de un campo personalizado de un elemento
     *
     * @return bool
     * @throws SPException
     */
    public function updateCustomField()
    {
        if (!self::checkCustomFieldExists($this->_module, $this->_itemId, $this->_id)) {
            return $this->addCustomField();
        }

        if (empty($this->_value)) {
            return self::deleteCustomFieldForItem($this->_itemId, $this->_module);
        }

        $cryptData = Crypt::encryptData($this->_value);

        $query = 'UPDATE customFieldsData SET ' .
            'customfielddata_data = :data, ' .
            'customfielddata_iv = :iv ' .
            'WHERE customfielddata_moduleId = :moduleid ' .
            'AND customfielddata_itemId = :itemid ' .
            'AND customfielddata_defId = :defid';

        $data['itemid'] = $this->_itemId;
        $data['moduleid'] = $this->_module;
        $data['defid'] = $this->_id;
        $data['data'] = $cryptData['data'];
        $data['iv'] = $cryptData['iv'];

        $queryRes = DB::getQuery($query, __FUNCTION__, $data);

        return $queryRes;
    }

    /**
     * Comprueba si el elemento tiene campos personalizados con datos
     *
     * @param int  $moduleId El id del módulo
     * @param int  $itemId   El id del elemento
     * @param null $defId    El id de la definición del campo
     * @return bool
     */
    public static function checkCustomFieldExists($moduleId, $itemId, $defId = null)
    {
        $query = 'SELECT customfielddata_id ' .
            'FROM customFieldsData ' .
            'WHERE customfielddata_moduleId = :moduleid ' .
            'AND customfielddata_itemId = :itemid ';

        $data['itemid'] = $itemId;
        $data['moduleid'] = $moduleId;

        if (!is_null($defId)) {
            $query .= 'AND customfielddata_defId = :defid';
            $data['defid'] = $defId;
        }

        DB::getQuery($query, __FUNCTION__, $data);

        return (DB::$lastNumRows >= 1);
    }

    /**
     * Añade los datos de un campo personalizado de un elemento
     *
     * @return bool
     * @throws SPException
     */
    public function addCustomField()
    {
        if (empty($this->_value)) {
            return true;
        }

        $cryptData = Crypt::encryptData($this->_value);

        $query = 'INSERT INTO customFieldsData SET ' .
            'customfielddata_itemId = :itemid, ' .
            'customfielddata_moduleId = :moduleid, ' .
            'customfielddata_defId = :defid, ' .
            'customfielddata_data = :data, ' .
            'customfielddata_iv = :iv';

        $data['itemid'] = $this->_itemId;
        $data['moduleid'] = $this->_module;
        $data['defid'] = $this->_id;
        $data['data'] = $cryptData['data'];
        $data['iv'] = $cryptData['iv'];

        $queryRes = DB::getQuery($query, __FUNCTION__, $data);

        return $queryRes;
    }

    /**
     * Eliminar los datos de un campo personalizado o los de una definición de campos
     *
     * @param int $itemId El Id del elemento asociado al campo
     * @return bool
     */
    public static function deleteCustomFieldForItem($itemId, $moduleId)
    {
        $query = 'DELETE FROM customFieldsData ' .
            'WHERE customfielddata_itemId = :itemid ' .
            'AND customfielddata_moduleId = :moduleid LIMIT 1';
        $data['itemid'] = $itemId;
        $data['moduleid'] = $moduleId;

        $queryRes = DB::getQuery($query, __FUNCTION__, $data);

        return $queryRes;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->_value;
    }
}