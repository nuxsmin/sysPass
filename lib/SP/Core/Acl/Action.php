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

namespace SP\Core\Acl;

use SP\DataModel\ActionData;
use SP\Storage\DbWrapper;
use SP\Storage\FileStorageInterface;
use SP\Storage\QueryData;

/**
 * Class Action
 *
 * @package SP\Core\Acl
 */
class Action
{
    /** Cache file name */
    const CACHE_NAME = 'actions';
    /** Cache expire time */
    const CACHE_EXPIRE = 86400;
    /** @var  int */
    protected $lastLoadTime;
    /** @var  ActionData[] */
    protected $actions;

    /**
     * Action constructor.
     *
     * @param FileStorageInterface $fileStorage
     */
    public function __construct(FileStorageInterface $fileStorage)
    {
        $this->loadCache($fileStorage);
    }

    /**
     * Loads actions from cache file
     *
     * @param FileStorageInterface $fileStorage
     */
    protected function loadCache(FileStorageInterface $fileStorage)
    {
        $fileName = CACHE_PATH . DIRECTORY_SEPARATOR . self::CACHE_NAME;

        if (!file_exists($fileName) || filemtime($fileName) + self::CACHE_EXPIRE < time()) {
            $this->mapFromDb();
            $this->saveCache($fileStorage);
        } else {
            $this->actions = $fileStorage->load($fileName);

            if ($this->actions === false) {
                $this->mapFromDb();
                $this->saveCache($fileStorage);
            }
        }
    }

    /**
     * Sets an array of actions using action_id as key
     */
    protected function mapFromDb()
    {
        $this->actions = [];

        foreach ($this->loadDb() as $action) {
            $this->actions[$action->getActionId()] = $action;
        }
    }

    /**
     * Loads actions from DB
     *
     * @return ActionData[]
     */
    protected function loadDb()
    {
        $query = /** @lang SQL */
            'SELECT action_id, action_name, action_text, action_route FROM actions ORDER BY action_id';

        $data = new QueryData();
        $data->setMapClassName(ActionData::class);
        $data->setQuery($query);

        return DbWrapper::getResultsArray($data);
    }

    /**
     * Saves actions into cache file
     *
     * @param FileStorageInterface $fileStorage
     */
    protected function saveCache(FileStorageInterface $fileStorage)
    {
        $fileStorage->save(CACHE_PATH . DIRECTORY_SEPARATOR . self::CACHE_NAME, $this->actions);
    }

    /**
     * Returns an action by id
     *
     * @param $id
     * @return ActionData
     */
    public function getActionById($id)
    {
        return $this->actions[$id];
    }
}