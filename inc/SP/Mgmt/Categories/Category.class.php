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

namespace SP\Mgmt\Categories;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CategoryData;
use SP\DataModel\CustomFieldData;
use SP\Log\Email;
use SP\Mgmt\CustomFields\CustomField;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemSelectInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DB;
use SP\Html\Html;
use SP\Log\Log;
use SP\Storage\QueryData;


/**
 * Esta clase es la encargada de realizar las operaciones sobre las categorías de sysPass.
 */
class Category extends CategoryBase implements ItemInterface, ItemSelectInterface
{
    use ItemTrait;

    /**
     * @return $this
     * @throws SPException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()) {
            throw new SPException(SPException::SP_WARNING, _('Nombre de categoría duplicado'));
        }

        $query = /** @lang SQL */
            'INSERT INTO categories SET category_name = ?, category_description = ?, category_hash = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getCategoryName());
        $Data->addParam($this->itemData->getCategoryDescription());
        $Data->addParam($this->makeItemHash($this->itemData->getCategoryName()));

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al crear la categoría'));
        }

        $this->itemData->setCategoryId(DB::$lastId);

        $Log = new Log(_('Nueva Categoría'));
        $Log->addDetails(Html::strongText(_('Categoría')), $this->itemData->getCategoryName());
        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * Comprobar duplicados
     *
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkDuplicatedOnAdd()
    {
        $query = /** @lang SQL */
            'SELECT category_id FROM categories WHERE category_hash = ? OR category_name = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($this->itemData->getCategoryName()));
        $Data->addParam($this->itemData->getCategoryName());

        return (DB::getQuery($Data) === false || $Data->getQueryNumRows() >= 1);
    }

    /**
     * @param $id int|array
     * @return mixed
     * @throws \SP\Core\Exceptions\SPException
     */
    public function delete($id)
    {
        if (is_array($id)) {
            foreach ($id as $itemId) {
                $this->delete($itemId);
            }

            return $this;
        }

        if ($this->checkInUse($id)) {
            throw new SPException(SPException::SP_WARNING, _('No es posible eliminar'));
        }

        $oldCategory = $this->getById($id);

        $query = /** @lang SQL */
            'DELETE FROM categories WHERE category_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al eliminar la categoría'));
        }

        $Log = new Log(_('Eliminar Categoría'));
        $Log->addDetails(Html::strongText(_('Categoría')), sprintf('%s (%d)', $oldCategory->getCategoryName(), $id));

        try {
            $CustomFieldData = new CustomFieldData();
            $CustomFieldData->setModule(ActionsInterface::ACTION_MGM_CATEGORIES);
            CustomField::getItem($CustomFieldData)->delete($id);
        } catch (SPException $e) {
            $Log->setLogLevel(Log::ERROR);
            $Log->addDescription($e->getMessage());
        }

        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @param $id int
     * @return mixed
     */
    public function checkInUse($id)
    {
        $query = /** @lang SQL */
            'SELECT account_id FROM accounts WHERE account_categoryId = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        DB::getQuery($Data);

        return $Data->getQueryNumRows() > 0;
    }

    /**
     * @param $id int
     * @return CategoryData
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT category_id, category_name, category_description FROM categories WHERE category_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setMapClassName($this->getDataModel());

        return DB::getResults($Data);
    }

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function update()
    {
        if ($this->checkDuplicatedOnUpdate()) {
            throw new SPException(SPException::SP_WARNING, _('Nombre de categoría duplicado'));
        }

        $oldCategory = $this->getById($this->itemData->getCategoryId());

        $query = /** @lang SQL */
            'UPDATE categories
              SET category_name = ?,
              category_description = ?,
              category_hash = ?
              WHERE category_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getCategoryName());
        $Data->addParam($this->itemData->getCategoryDescription());
        $Data->addParam($this->makeItemHash($this->itemData->getCategoryName()));
        $Data->addParam($this->itemData->getCategoryId());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al actualizar la categoría'));
        }

        $Log = new Log(_('Modificar Categoría'));
        $Log->addDetails(Html::strongText(_('Nombre')), sprintf('%s > %s', $oldCategory->getCategoryName(), $this->itemData->getCategoryName()));
        $Log->addDetails(Html::strongText(_('Descripción')), sprintf('%s > %s', $oldCategory->getCategoryDescription(), $this->itemData->getCategoryDescription()));
        $Log->writeLog();

        Email::sendEmail($Log);

        return $this;
    }

    /**
     * @return mixed
     */
    public function checkDuplicatedOnUpdate()
    {
        $query = /** @lang SQL */
            'SELECT category_id FROM categories WHERE (category_hash = ? OR category_name = ?) AND category_id <> ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->makeItemHash($this->itemData->getCategoryName()));
        $Data->addParam($this->itemData->getCategoryName());
        $Data->addParam($this->itemData->getCategoryId());

        return (DB::getQuery($Data) === false || $Data->getQueryNumRows() > 0);
    }

    /**
     * @return CategoryData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT category_id, category_name, category_description FROM categories ORDER BY category_name';

        $Data = new QueryData();
        $Data->setMapClassName($this->getDataModel());
        $Data->setQuery($query);

        return DB::getResultsArray($Data);
    }
}