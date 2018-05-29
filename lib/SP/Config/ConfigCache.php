<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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

namespace SP\Config;

use SP\Storage\FileCache;
use SP\Storage\FileException;

/**
 * Class ConfigCache
 *
 * @package SP\Config
 */
class ConfigCache
{
    /**
     * Cache file name
     */
    const CONFIG_CACHE_FILE = CACHE_PATH . DIRECTORY_SEPARATOR . 'config.cache';
    /**
     * @var FileCache
     */
    private $fileCache;


    /**
     * ConfigCache constructor.
     *
     * @param FileCache $fileCache
     */
    public function __construct(FileCache $fileCache)
    {
        $this->fileCache = $fileCache;
    }

    /**
     * Saves config into the cache file
     *
     * @param ConfigData $configData
     */
    public function saveConfigToCache(ConfigData $configData)
    {
        try {
            $this->fileCache->save(self::CONFIG_CACHE_FILE, $configData);

            debugLog('Saved config cache');
        } catch (FileException $e) {
            processException($e);
        }
    }

    /**
     * Loads config from the cache file
     *
     * @return ConfigData
     */
    public function loadConfigFromCache()
    {
        try {
            $configData = $this->fileCache->load(self::CONFIG_CACHE_FILE);

            if ($configData instanceof ConfigData) {
                debugLog('Loaded config cache');

                return $configData;
            }
        } catch (FileException $e) {
            processException($e);
        }

        return null;
    }
}