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

namespace SP;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class Themes para el manejo de los temas visuales
 *
 * @package SP
 */
class Themes
{
    /**
     * @var string
     */
    public static $themeUri = '';
    /**
     * @var string
     */
    public static $themePath = '';
    /**
     * @var string
     */
    public static $theme = '';

    /**
     * Obtener los temas disponibles desde el directorio de temas
     *
     * @return array Con la información del tema
     */
    public static function getThemesAvailable()
    {
        $themesAvailable = array();

        $dirThemes = dir(VIEW_PATH);

        while (false !== ($theme = $dirThemes->read())) {
            if ($theme != '.' && $theme != '..') {
                $themeFile = VIEW_PATH . DIRECTORY_SEPARATOR . $theme . DIRECTORY_SEPARATOR . 'index.php';

                if (file_exists($themeFile)) {
                    include $themeFile;

                    $themesAvailable[$theme] = $themeInfo['name'];
                }
            }
        }

        $dirThemes->close();

        return $themesAvailable;
    }

    /**
     * Establecer el tema visual a utilizar
     *
     * @param bool $force Forzar la detección del tema para los inicios de sesión
     */
    public static function setTheme($force = false)
    {
        $theme = Session::getTheme();

        if (empty($theme) || $force === true) {
            $Theme = new Themes();

            $userTheme = $Theme->getUserTheme();
            $globalTheme = $Theme->getGlobalTheme();

            $theme = ($userTheme) ? $userTheme : $globalTheme;

            Session::setTheme($theme);
        }

        self::setThemePaths($theme);
        Session::setTheme($theme);
    }

    /**
     * Obtener el tema visual del usuario
     *
     * @return string
     */
    private function getUserTheme()
    {
        return (Session::getUserId() > 0) ? UserPreferences::getPreferences(Session::getUserId())->getTheme() : '';
    }

    /**
     * Devolver el tema visual de sysPass desde la configuración
     */
    private function getGlobalTheme()
    {
        self::$theme = Config::getValue('sitetheme', 'material-blue');

        return self::$theme;
    }

    /**
     * Establecer las variables de rutas para el tema visual
     *
     * @param string $theme El tema a utilizar
     */
    private static function setThemePaths($theme)
    {
        self::$theme = $theme;
        self::$themeUri = Init::$WEBURI . '/inc/themes/' . $theme;
        self::$themePath = DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . $theme;
    }
}