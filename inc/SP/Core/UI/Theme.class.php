<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Core\UI;

use SP\Config\Config;
use SP\Core\Init;
use SP\Core\Session;
use SP\Mgmt\Users\UserPreferences;
use Theme\Icons;

defined('APP_ROOT') || die();

/**
 * Class Theme
 *
 * @package SP
 */
class Theme implements ThemeInterface
{
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
     * @var ThemeIconsInterface
     */
    protected $icons;

    /**
     * Theme constructor.
     */
    public function __construct()
    {
        $this->initTheme();
        $this->initIcons();
    }

    /**
     * Inicializar el tema visual a utilizar
     *
     * @param bool $force Forzar la detección del tema para los inicios de sesión
     * @return void
     */
    public function initTheme($force = false)
    {
        $this->themeName = Session::getTheme();

        if (empty($this->themeName) || $force === true) {
            $this->themeName = $this->getUserTheme() ?: $this->getGlobalTheme();
            Session::setTheme($this->themeName);
        }

        $this->themeUri = Init::$WEBURI . '/inc/themes/' . $this->themeName;
        $this->themePath = DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $this->themeName;
        $this->themePathFull = Init::$SERVERROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $this->themeName;
        $this->viewsPath = $this->themePathFull . DIRECTORY_SEPARATOR . 'views';
    }

    /**
     * Obtener el tema visual del usuario
     *
     * @return string
     */
    protected function getUserTheme()
    {
        return (Session::getUserData()->getUserId() > 0) ? Session::getUserPreferences()->getTheme() : '';
    }

    /**
     * Devolver el tema visual de sysPass desde la configuración
     */
    protected function getGlobalTheme()
    {
        $this->themeName = Config::getConfig()->getSiteTheme();

        return $this->themeName;
    }

    /**
     * Inicializar los iconos del tema actual
     *
     * @return ThemeIconsInterface
     */
    protected function initIcons()
    {
        $iconsClass = $this->themePathFull . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Icons.class.php';

        if (file_exists($iconsClass)) {
            include_once $iconsClass;

            $this->icons = new Icons();
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
        $themesAvailable = array();

        $themesDirs = dir(VIEW_PATH);

        while (false !== ($themeDir = $themesDirs->read())) {
            if ($themeDir != '.' && $themeDir != '..') {
                $themeFile = VIEW_PATH . DIRECTORY_SEPARATOR . $themeDir . DIRECTORY_SEPARATOR . 'index.php';

                if (file_exists($themeFile)) {
                    include $themeFile;

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
        $themeInfo = array();

        if (file_exists($themeFile)) {
            include $themeFile;
        }

        return $themeInfo;
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
     * @return ThemeIconsInterface
     */
    public function getIcons()
    {
        return $this->icons;
    }

    /**
     * @return string
     */
    public function getViewsPath()
    {
        return $this->viewsPath;
    }
}