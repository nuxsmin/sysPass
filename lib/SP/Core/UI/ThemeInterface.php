<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

/**
 * Interface ThemeInterface
 *
 * @package SP\Core\UI
 */
interface ThemeInterface
{
    /**
     * @param bool $force Forzar la detección del tema para los inicios de sesión
     *
     * @return mixed
     */
    public function initTheme($force = false);

    /**
     * Obtener los temas disponibles desde el directorio de temas
     *
     * @return array Con la información del tema
     */
    public function getThemesAvailable();

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
    public function getThemeInfo();

    /**
     * @return string
     */
    public function getThemeUri();

    /**
     * @return string
     */
    public function getThemePath();

    /**
     * @return string
     */
    public function getThemeName();

    /**
     * @return ThemeIcons
     */
    public function getIcons();

    /**
     * @return string
     */
    public function getViewsPath();
}