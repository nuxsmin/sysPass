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

namespace SP\Core\Plugin;

use SP\Core\Events\EventReceiver;

/**
 * Interface PluginInterface
 *
 * @package SP\Core\Plugin
 */
interface PluginInterface extends EventReceiver
{
    /**
     * Devuelve el tipo de plugin
     *
     * @return string
     */
    public function getType();

    /**
     * Devuelve el directorio base del plugin
     *
     * @return string
     */
    public function getBase();

    /**
     * Devuelve el directorio del tema usado
     *
     * @return mixed
     */
    public function getThemeDir();

    /**
     * Devuelve el autor del plugin
     *
     * @return string
     */
    public function getAuthor();

    /**
     * Devuelve la versión del plugin
     *
     * @return array
     */
    public function getVersion();

    /**
     * Devuelve la versión compatible de sysPass
     *
     * @return array
     */
    public function getCompatibleVersion();

    /**
     * Devuelve el nombre del plugin
     *
     * @return string
     */
    public function getName();

    /**
     * @return mixed
     */
    public function getData();

    /**
     * @param mixed $data
     */
    public function setData($data);

    /**
     * @return int
     */
    public function getEnabled();

    /**
     * @param int $enabled
     */
    public function setEnabled($enabled);
}