<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Plugin;

/**
 * Interface PluginInterface
 *
 * @package SP\Plugin
 */
interface PluginInterface extends PluginEventReceiver
{
    /**
     * Devuelve el tipo de plugin
     *
     * @return string|null
     */
    public function getType(): ?string;

    /**
     * Devuelve el directorio base del plugin
     *
     * @return string|null
     */
    public function getBase(): ?string;

    /**
     * Devuelve el directorio del tema usado
     *
     * @return string|null
     */
    public function getThemeDir(): ?string;

    /**
     * Devuelve el autor del plugin
     *
     * @return string|null
     */
    public function getAuthor(): ?string;

    /**
     * Devuelve la versión del plugin
     *
     * @return array|null
     */
    public function getVersion(): ?array;

    /**
     * Devuelve la versión compatible de sysPass
     *
     * @return array|null
     */
    public function getCompatibleVersion(): ?array;

    /**
     * Devuelve el nombre del plugin
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @return mixed|null
     */
    public function getData();

    /**
     * onLoad
     */
    public function onLoad();

    /**
     * @return int
     */
    public function getEnabled();

    /**
     * @param int $enabled
     */
    public function setEnabled(int $enabled);

    /**
     * @param string          $version
     * @param PluginOperation $pluginOperation
     * @param mixed           $extra
     */
    public function upgrade(string $version, PluginOperation $pluginOperation, $extra = null);
}