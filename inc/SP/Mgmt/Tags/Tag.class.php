<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Mgmt\Tags;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\Exceptions\SPException;
use SP\DataModel\TagData;
use SP\Mgmt\ItemInterface;
use SP\Mgmt\ItemSelectInterface;
use SP\Mgmt\ItemTrait;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class Tags
 *
 * @package SP\Mgmt\Tags
 */
class Tag extends TagBase implements ItemInterface, ItemSelectInterface
{
    use ItemTrait;

    /**
     * @return $this
     * @throws \SP\Core\Exceptions\SPException
     */
    public function add()
    {
        if ($this->checkDuplicatedOnAdd()) {
            throw new SPException(SPException::SP_INFO, _('Etiqueta duplicada'));
        }

        $query = /** @lang SQL */
            'INSERT INTO tags SET tag_name = ?, tag_hash = ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getTagName());
        $Data->addParam($this->itemData->getTagHash());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al crear etiqueta'));
        }

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkDuplicatedOnAdd()
    {
        $query = /** @lang SQL */
            'SELECT tag_id FROM tags WHERE tag_hash = ?';
        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getTagHash());

        $queryRes = DB::getResults($Data);

        if ($queryRes !== false) {
            if ($Data->getQueryNumRows() === 0) {
                return false;
            } elseif ($Data->getQueryNumRows() === 1) {
                $this->itemData->setTagId($queryRes->tag_id);
            }
        }

        return true;
    }

    /**
     * @param $id int|array
     * @return $this
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

        $query = /** @lang SQL */
            'DELETE FROM tags WHERE tag_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al eliminar etiqueta'));
        }

        return $this;
    }

    /**
     * @return $this
     * @throws SPException
     */
    public function update()
    {
        if ($this->checkDuplicatedOnUpdate()) {
            throw new SPException(SPException::SP_INFO, _('Etiqueta duplicada'));
        }

        $query = /** @lang SQL */
            'UPDATE tags SET tag_name = ?, tag_hash = ? WHERE tag_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getTagName());
        $Data->addParam($this->itemData->getTagHash());
        $Data->addParam($this->itemData->getTagId());

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al actualizar etiqueta'));
        }

        return $this;
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public function checkDuplicatedOnUpdate()
    {
        $query = /** @lang SQL */
            'SELECT tag_hash FROM tags WHERE tag_hash = ? AND tag_id <> ?';
        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->itemData->getTagHash());
        $Data->addParam($this->itemData->getTagId());

        return (DB::getQuery($Data) === false || $Data->getQueryNumRows() > 0);
    }

    /**
     * @param $id int
     * @return TagData
     * @throws SPException
     */
    public function getById($id)
    {
        $query = /** @lang SQL */
            'SELECT tag_id, tag_name FROM tags WHERE tag_id = ? LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($id);
        $Data->setMapClassName($this->getDataModel());

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al obtener etiqueta'));
        }

        return $queryRes;
    }

    /**
     * @return TagData[]
     */
    public function getAll()
    {
        $query = /** @lang SQL */
            'SELECT tag_id, tag_name, tag_hash FROM tags ORDER BY tag_name';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName($this->getDataModel());

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
}