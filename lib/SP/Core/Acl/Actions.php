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
use SP\Storage\FileStorageInterface;
use SP\Storage\XmlFileStorageInterface;

/**
 * Class Actions
 *
 * @package SP\Core\Acl
 */
class Actions
{
    /**
     * Cache file name
     */
    const CACHE_NAME = 'actions';
    /**
     * Cache expire time
     */
    const CACHE_EXPIRE = 86400;
    /**
     * @var  int
     */
    protected $lastLoadTime;
    /**
     * @var  ActionData[]
     */
    protected $actions;
    /**
     * @var XmlFileStorageInterface
     */
    protected $xmlFileStorage;

    /**
     * Action constructor.
     *
     * @param FileStorageInterface    $fileStorage
     * @param XmlFileStorageInterface $xmlFileStorage
     */
    public function __construct(FileStorageInterface $fileStorage, XmlFileStorageInterface $xmlFileStorage)
    {
        $this->xmlFileStorage = $xmlFileStorage;

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
            $this->map();
            $this->saveCache($fileStorage);
        } else {
            $this->actions = $fileStorage->load($fileName);

            if ($this->actions === false) {
                $this->map();
                $this->saveCache($fileStorage);
            }
        }
    }

    /**
     * Sets an array of actions using id as key
     */
    protected function map()
    {
        debugLog('ACTION CACHE MISS');

        $this->actions = [];

        $actionBase = new ActionData();

        foreach ($this->load() as $a) {
            if (isset($this->actions[$a['id']])) {
                throw new \RuntimeException('Duplicated action id');
            }

            $action = clone $actionBase;
            $action->id = $a['id'];
            $action->name = $a['name'];
            $action->text = $a['text'];
            $action->route = $a['route'];

            $this->actions[$action->id] = $action;
        }
    }

    /**
     * Loads actions from DB
     *
     * @return ActionData[]
     */
    protected function load()
    {
        return $this->xmlFileStorage->load('actions')->getItems();
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