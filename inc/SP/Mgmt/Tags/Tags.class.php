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

use SP\DataModel\TagData;
use SP\Storage\DB;
use SP\Storage\DBUtil;
use SP\Storage\QueryData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class Tags
 *
 * @package SP\Mgmt\Tags
 */
class Tags
{
    /**
     * Obtiene el listado de etiquetas mediante una búsqueda
     *
     * @param int    $limitCount
     * @param int    $limitStart
     * @param string $search La cadena de búsqueda
     * @return array con el id de categoria como clave y en nombre como valor
     */
    public static function getTagsMgmtSearch($limitCount, $limitStart = 0, $search = "")
    {
        $query = 'SELECT tag_id, tag_name FROM tags';

        $Data = new QueryData();

        if (!empty($search)) {
            $query .= ' WHERE tag_name LIKE ? ';
            $Data->addParam('%' . $search . '%');
        }

        $query .= ' ORDER BY tag_name';
        $query .= ' LIMIT ?,?';

        $Data->addParam($limitStart);
        $Data->addParam($limitCount);

        $Data->setQuery($query);

        DB::setReturnArray();
        DB::setFullRowCount();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return array();
        }

        $queryRes['count'] = DB::$lastNumRows;

        return $queryRes;
    }

    /**
     * Devolver los tags disponibles
     *
     * @return TagData[]
     */
    public static function getTags()
    {
        $query = 'SELECT tag_id, tag_name FROM tags';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName('SP\DataModel\TagData');

        DB::setReturnArray();

        return DB::getResults($Data);
    }

    /**
     * Devolver los tags disponibles
     *
     * @return TagData[]
     */
    public static function getTagsForJson()
    {
        $query = 'SELECT tag_id, tag_name FROM tags ORDER BY tag_name';

        $Data = new QueryData();
        $Data->setQuery($query);

        DB::setReturnArray();

        return DB::getResults($Data);
    }

    /**
     * @param TagData $tag
     * @return TagData
     */
    public function getTag(TagData $tag)
    {
        if (!$tag->getTagId()) {
            return $tag;
        }

        $query = 'SELECT tag_id, tag_name FROM tags WHERE tag_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($tag->getTagId(), 'id');
        $Data->setMapClassName('SP\DataModel\TagData');

        return DB::getResults($Data);
    }

    /**
     * @param TagData $tag
     * @return bool
     */
    public function addTag(TagData $tag)
    {
        $query = 'INSERT INTO tags SET tag_name = :name, tag_hash = :hash';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($tag->getTagName(), 'name');
        $Data->addParam($this->getTagHash($tag), 'hash');

        return DB::getQuery($Data);
    }

    /**
     * @param TagData $tag
     * @return bool
     */
    public function deleteTag(TagData $tag)
    {
        $query = 'DELETE FROM tags WHERE tag_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($tag->getTagId(), 'id');

        return DB::getQuery($Data);
    }

    /**
     * @param TagData $tag
     * @return bool
     */
    public function updateTag(TagData $tag)
    {
        $query = 'UPDATE tags SET tag_name = :name, tag_hash = :hash WHERE tag_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($tag->getTagId(), 'id');
        $Data->addParam($tag->getTagName(), 'name');
        $Data->addParam($this->getTagHash($tag), 'hash');

        return DB::getQuery($Data);
    }

    /**
     * Formatear el nombre de la etiqueta y devolver un hash
     *
     * @param TagData $tag
     * @return string
     */
    public function getTagHash(TagData $tag)
    {
        $charsSrc = array(".", " ", "_", ", ", "-", ";", "'", "\"", ":", "(", ")", "|", "/");

        return sha1(strtolower(str_replace($charsSrc, '', DBUtil::escape($tag->getTagName()))));
    }
}