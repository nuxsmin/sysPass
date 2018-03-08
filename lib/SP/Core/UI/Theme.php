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

namespace SP\Core\UI;

use SP\Bootstrap;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Session\Session;
use SP\Storage\FileCache;
use SP\Storage\FileException;
use Theme\Icons;

defined('APP_ROOT') || die();

/**
 * Class Theme
 *
 * @package SP
 */
class Theme implements ThemeInterface
{
    const ICONS_CACHE_FILE = CACHE_PATH . DIRECTORY_SEPARATOR . 'icons.cache';
    /**
     * Cache expire time
     */
    const CACHE_EXPIRE = 86400;
    /**
     * @var string
     */
    protected $themeUri = '';
    /**
     * @var string
     */
    protected $themePath = '';
    /**
     * @var string
     */
    protected $themePathFull = '';
    /**
     * @var string
     */
    protected $themeName = '';
    /**
     * @var string
     */
    protected $viewsPath = '';
    /**
     * @var ThemeIcons
     */
    protected $icons;
    /**
     * @var ConfigData
     */
    protected $configData;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var string
     */
    protected $module;
    /**
     * @var FileCache
     */
    private $fileCache;

    /**
     * Theme constructor.
     *
     * @param string    $module
     * @param Config    $config
     * @param Session   $session
     * @param FileCache $fileCache
     */
    public function __construct($module, Config $config, Session $session, FileCache $fileCache)
    {
        $this->configData = $config->getConfigData();
        $this->session = $session;
        $this->fileCache = $fileCache;

        if (is_dir(VIEW_PATH)) {
            $this->initTheme();
            $this->initIcons();
        }
    }

    /**
     * Inicializar el tema visual a utilizar
     *
     * @param bool $force Forzar la detección del tema para los inicios de sesión
     * @return void
     */
    public function initTheme($force = false)
    {
        $this->themeName = $this->session->getTheme();

        if (empty($this->themeName) || $force === true) {
            $this->themeName = $this->getUserTheme() ?: $this->getGlobalTheme();
            $this->session->setTheme($this->themeName);
        }

        $this->themeUri = Bootstrap::$WEBURI . '/app/modules/' . $this->module . 'themes' . $this->themeName;
        $this->themePath = str_replace(APP_ROOT, '', VIEW_PATH) . DIRECTORY_SEPARATOR . $this->themeName;
        $this->themePathFull = VIEW_PATH . DIRECTORY_SEPARATOR . $this->themeName;
        $this->viewsPath = $this->themePathFull . DIRECTORY_SEPARATOR . 'views';
    }

    /**
     * Obtener el tema visual del usuario
     *
     * @return string
     */
    protected function getUserTheme()
    {
        $userData = $this->session->getUserData();

        return ($userData->getId() > 0) ? $userData->getPreferences()->getTheme() : '';
    }

    /**
     * Devolver el tema visual de sysPass desde la configuración
     */
    protected function getGlobalTheme()
    {
        return $this->configData->getSiteTheme();
    }

    /**
     * Inicializar los iconos del tema actual
     *
     * @return ThemeIconsInterface
     * @throws InvalidClassException
     */
    protected function initIcons()
    {
        if (!$this->fileCache->isExpired(self::ICONS_CACHE_FILE, self::CACHE_EXPIRE)) {
            try {
                $this->icons = $this->fileCache->load(self::ICONS_CACHE_FILE);

                debugLog('Loaded icons cache');

                return $this->icons;
            } catch (FileException $e) {
                processException($e);
            }
        }

        $iconsClass = $this->themePathFull . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Icons.php';

        if (file_exists($iconsClass)) {
            if (!($this->icons = require $iconsClass) instanceof ThemeIcons) {
                throw new InvalidClassException(__u('Clase no válida para iconos'));
            }

            try {
                $this->fileCache->save(self::ICONS_CACHE_FILE, $this->icons);

                debugLog('Saved icons cache');
            } catch (FileException $e) {
                processException($e);
            }
        }

        return $this->icons;
    }

    /**
     * Obtener los temas disponibles desde el directorio de temas
     *
     * @return array Con la información del tema
     */
    public function getThemesAvailable()
    {
        $themesAvailable = [];

        $themesDirs = dir(VIEW_PATH);

        while (false !== ($themeDir = $themesDirs->read())) {
            if ($themeDir !== '.' && $themeDir !== '..') {
                $themeFile = $this->themePathFull . DIRECTORY_SEPARATOR . 'index.php';

                if (file_exists($themeFile)) {
                    $themeInfo = require $themeFile;

                    $themesAvailable[$themeDir] = $themeInfo['name'];
                }
            }
        }

        $themesDirs->close();

        return $themesAvailable;
    }

    /**
     * Obtener la información del tema desde el archivo de información
     *
     * @return array (
     *          'name' => string
     *          'creator' => string
     *          'version' => string
     *          'js' => array
     *          'css' => array
     *  )
     */
    public function getThemeInfo()
    {
        $themeFile = $this->themePathFull . DIRECTORY_SEPARATOR . 'index.php';

        if (file_exists($themeFile)) {
            return include $themeFile;
        }

        return [];
    }

    /**
     * @return string
     */
    public function getThemeUri()
    {
        return $this->themeUri;
    }

    /**
     * @return string
     */
    public function getThemePath()
    {
        return $this->themePath;
    }

    /**
     * @return string
     */
    public function getThemeName()
    {
        return $this->themeName;
    }

    /**
     * @return ThemeIcons
     */
    public function getIcons()
    {
        return clone $this->icons;
    }

    /**
     * @return string
     */
    public function getViewsPath()
    {
        return $this->viewsPath;
    }
}