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

namespace SP\Mgmt\CustomFields;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\SPException;
use SP\DataModel\CustomFieldDefData;
use SP\Mgmt\ItemInterface;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Class CustomFieldDef para la gestión de definiciones de campos personalizados
 *
 * @package SP
 */
class CustomFieldDef extends CustomFieldBase implements ItemInterface
{
    /**
     * Category constructor.
     *
     * @param CustomFieldDefData $itemData
     */
    public function __construct(CustomFieldDefData $itemData = null)
    {
        $this->itemData = (!is_null($itemData)) ? $itemData : new CustomFieldDefData();
    }

    /**
     * @return mixed
     * @throws SPException
     */
    public function add()
    {
        $query = /** @lang SQL */
            'INSERT INTO customFieldsDef SET customfielddef_module = ?, customfielddef_field = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getModule());
        $Data->addParam(serialize($this->itemData));

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al crear el campo personalizado'));
        }

        return $this;
    }

    /**
     * @param $id int
     * @return mixed
     * @throws SPException
     */
    public function delete($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM customFieldsDef WHERE customfielddef_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        if (DB::getQuery($Data) === false
            || $this->deleteItemsDataForDefinition($id) === false
        ) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al eliminar el campo personalizado'));
        }

        return $this;
    }

    /**
     * Eliminar los datos de los elementos de una definición
     *
     * @param $id
     * @return bool
     */
    protected function deleteItemsDataForDefinition($id)
    {
        $query = /** @lang SQL */
            'DELETE FROM customFieldsData WHERE customfielddata_defId = ?';
        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        return DB::getQuery($Data);
    }

    /**
     * @return mixed
     * @throws SPException
     */
    public function update()
    {
        $curField = $this->getById($this->itemData->getId());

        $query = /** @lang SQL */
            'UPDATE customFieldsDef SET
            customfielddef_module = ?,
            customfielddef_field = ?
            WHERE customfielddef_id= ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getModule());
        $Data->addParam(serialize($this->itemData));
        $Data->addParam($this->itemData->getId());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al actualizar el campo personalizado'));
        }

        if ($curField->getModule() !== $this->itemData->getModule()) {
            $this->updateItemsModulesForDefinition();
        }

        return $this;
    }

    /**
     * @param $id int
     * @return $this
     * @throws SPException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT customfielddef_id,
              customfielddef_module,
              customfielddef_field
              FROM customFieldsDef
              WHERE customfielddef_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName('SP\DataModel\CustomFieldDefData');
        $Data->setQuery($query);
        $Data->addParam($id);

        $CustomFieldDef = DB::getResults($Data);

        if ($CustomFieldDef === false) {
            throw new SPException(SPException::SP_WARNING, _('Campo personalizado no encontrado'));
        }

        /**
         * @var CustomFieldDefData $CustomFieldDef
         * @var CustomFieldDefData $fieldDef
         */

        $fieldDef = unserialize($CustomFieldDef->getCustomfielddefField());

        if (get_class($fieldDef) === '__PHP_Incomplete_Class') {
            $fieldDef = Util::castToClass('SP\DataModel\CustomFieldDefData', $fieldDef);
        }

        $fieldDef->setId($CustomFieldDef->getCustomfielddefId());

        $this->itemData = $fieldDef;

        return $this;
    }

    /**
     * Actualizar el módulo de los elementos con campos personalizados
     *
     * @return bool
     */
    protected function updateItemsModulesForDefinition()
    {
        $query = /** @lang SQL */
            'UPDATE customFieldsData SET
            customfielddata_moduleId = ?
            WHERE customfielddata_defId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getModule());
        $Data->addParam($this->itemData->getId());

        return DB::getQuery($Data);
    }

    /**
     * @return CustomFieldDefData[]|array
     * @throws SPException
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT customfielddef_id,
              customfielddef_module,
              customfielddef_field
              FROM customFieldsDef
              ORDER BY customfielddef_module';

        $Data = new QueryData();
        $Data->setMapClassName('SP\DataModel\CustomFieldDefData');
        $Data->setQuery($query);

        DB::setReturnArray();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_WARNING, _('No se encontraron campos personalizados'));
        }

        $fields = [];

        foreach ($queryRes as $CustomFieldDef) {
            /**
             * @var CustomFieldDefData $CustomFieldDef
             * @var CustomFieldDefData $fieldDef
             */

            $fieldDef = unserialize($CustomFieldDef->getCustomfielddefField());

            if (get_class($fieldDef) === '__PHP_Incomplete_Class') {
                $fieldDef = Util::castToClass('SP\DataModel\CustomFieldDefData', $fieldDef);
            }

            $fieldDef->setId($CustomFieldDef->getCustomfielddefId());

            $fields[] = $fieldDef;
        }

        return $fields;
    }

    /**
     * @param $id int
     * @return mixed
     */
    public function checkInUse($id)
    {
        // TODO: Implement checkInUse() method.
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnUpdate()
    {
        // TODO: Implement checkDuplicatedOnUpdate() method.
    }

    /**
     * @return bool
     */
    public function checkDuplicatedOnAdd()
    {
        // TODO: Implement checkDuplicatedOnAdd() method.
    }
}