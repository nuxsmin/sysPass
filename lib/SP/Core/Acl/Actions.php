<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Storage\FileException;
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
    const ACTIONS_CACHE_FILE = CACHE_PATH . DIRECTORY_SEPARATOR . 'actions.cache';
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
     * @var FileStorageInterface
     */
    private $fileStorage;

    /**
     * Action constructor.
     *
     * @param FileStorageInterface $fileStorage
     * @param XmlFileStorageInterface $xmlFileStorage
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    public function __construct(FileStorageInterface $fileStorage, XmlFileStorageInterface $xmlFileStorage)
    {
        $this->xmlFileStorage = $xmlFileStorage;
        $this->fileStorage = $fileStorage;

        $this->loadCache();
    }

    /**
     * Loads actions from cache file
     *
     * @return void
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    protected function loadCache()
    {
        if ($this->fileStorage->isExpired(self::ACTIONS_CACHE_FILE, self::CACHE_EXPIRE)) {
            $this->mapAndSave();
        } else {
            try {
                $this->actions = $this->fileStorage->load(self::ACTIONS_CACHE_FILE);

                debugLog('Loaded actions cache');
            } catch (FileException $e) {
                processException($e);

                $this->mapAndSave();
            }
        }
    }

    /**
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    protected function mapAndSave()
    {
        debugLog('ACTION CACHE MISS');

        $this->map();
        $this->saveCache();
    }

    /**
     * Sets an array of actions using id as key
     *
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    protected function map()
    {
        $this->actions = [];

        $actionBase = new ActionData();

        foreach ($this->load() as $a) {
            if (isset($this->actions[$a['id']])) {
                throw new \RuntimeException('Duplicated action id: ' . $a['id']);
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
     * @throws \SP\Core\Exceptions\FileNotFoundException
     */
    protected function load()
    {
        return $this->xmlFileStorage->load('actions')->getItems();
    }

    /**
     * Saves actions into cache file
     */
    protected function saveCache()
    {
        try {
            $this->fileStorage->save(self::ACTIONS_CACHE_FILE, $this->actions);

            debugLog('Saved actions cache');
        } catch (FileException $e) {
            processException($e);
        }
    }

    /**
     * Returns an action by id
     *
     * @param $id
     * @return ActionData
     * @throws ActionNotFoundException
     */
    public function getActionById($id)
    {
        if (!isset($this->actions[$id])) {
            throw new ActionNotFoundException(__u('Acción no encontrada'));
        }

        return $this->actions[$id];
    }
}