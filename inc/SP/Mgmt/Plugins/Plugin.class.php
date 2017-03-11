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

namespace SP\Mgmt\Plugins;

use SP\Core\Exceptions\SPException;
use SP\DataModel\PluginData;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class Plugin
 *
 * @package SP\Mgmt\Plugins
 * @property PluginData $itemData
 */
class Plugin extends PluginBase implements ItemInterface
{
    use ItemTrait;

    /**
     * Añade un nuevo plugin
     *
     * @return $this
     * @throws SPException
     */
    public function add()
    {
        $query = /** @lang SQL */
            'INSERT INTO plugins SET plugin_name = ?, plugin_data = ?, plugin_enabled = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getPluginName());
        $Data->addParam($this->itemData->getPluginData());
        $Data->addParam($this->itemData->getPluginEnabled());
        $Data->setOnErrorMessage(__('Error al crear el plugin', false));

        DB::getQuery($Data);

        $this->itemData->setPluginId(DB::$lastId);

        return $this;
    }

    /**
     * Eliminar un plugin
     *
     * @param $name string
     * @return mixed
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($name)
    {
        $query = /** @lang SQL */
            'DELETE FROM plugins WHERE plugin_name = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($name);
        $Data->setOnErrorMessage(__('Error al eliminar el plugin', false));

        DB::getQuery($Data);

        if ($Data->getQueryNumRows() === 0) {
            throw new SPException(SPException::SP_INFO, __('Plugin no encontrado', false));
        }

        return $this;
    }

    /**
     * Actualizar los datos de un plugin
     *
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function update()
    {
        $query = /** @lang SQL */
            'UPDATE plugins
              SET plugin_name = ?,
              plugin_data = ?,
              plugin_enabled = ?
              WHERE plugin_name = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getPluginName());
        $Data->addParam($this->itemData->getPluginData());
        $Data->addParam($this->itemData->getPluginEnabled());
        $Data->addParam($this->itemData->getPluginName());
        $Data->setOnErrorMessage(__('Error al actualizar el plugin', false));

        DB::getQuery($Data);

        return $this;
    }

    /**
     * Devuelve los datos de un plugin por su id
     *
     * @param $id int
     * @return bool|PluginData
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT plugin_id, plugin_name, plugin_data, BIN(plugin_enabled) AS plugin_enabled FROM plugins WHERE plugin_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($id);

        return DB::getResults($Data);
    }

    /**
     * Devolver todos los plugins
     *
     * @return PluginData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT plugin_id, plugin_name, BIN(plugin_enabled) AS plugin_enabled FROM plugins ORDER BY plugin_name';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);

        return DB::getResultsArray($Data);
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

    /**
     * Devuelve los datos de un plugin por su nombre
     *
     * @param $name int
     * @return mixed
     */
    public function getByName($name)
    {
        $query = /** @lang SQL */
            'SELECT plugin_id, plugin_name, plugin_data, BIN(plugin_enabled) AS plugin_enabled FROM plugins WHERE plugin_name = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->addParam($name);

        return DB::getResults($Data);
    }

    /**
     * Cambiar el estado del plugin
     *
     * @return $this
     * @throws SPException
     */
    public function toggle()
    {
        $query = /** @lang SQL */
            'UPDATE plugins
              SET plugin_enabled = ?
              WHERE plugin_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getPluginEnabled());
        $Data->addParam($this->itemData->getPluginId());
        $Data->setOnErrorMessage(__('Error al actualizar el plugin', false));

        DB::getQuery($Data);

        return $this;
    }

    /**
     * Restablecer los datos de un plugin
     *
     * @param int $id Id del plugin
     * @return $this
     * @throws SPException
     */
    public function reset($id)
    {
        $query = /** @lang SQL */
            'UPDATE plugins
              SET plugin_data = NULL 
              WHERE plugin_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setOnErrorMessage(__('Error al actualizar el plugin', false));

        DB::getQuery($Data);

        return $this;
    }

    /**
     * Devolver los elementos con los ids especificados
     *
     * @param array $ids
     * @return PluginData[]
     */
    public function getByIdBatch(array $ids)
    {
        if (count($ids) === 0) {
            return [];
        }

        $query = /** @lang SQL */
            'SELECT plugin_id, plugin_name, BIN(plugin_enabled) AS plugin_enabled FROM plugins WHERE plugin_id IN (' . $this->getParamsFromArray($ids) . ')';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);
        $Data->setParams($ids);

        return DB::getResultsArray($Data);
    }
}